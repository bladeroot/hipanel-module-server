<?php
/**
 * Server module for HiPanel
 *
 * @link      https://github.com/hiqdev/hipanel-module-server
 * @package   hipanel-module-server
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2015-2019, HiQDev (http://hiqdev.com/)
 */

namespace hipanel\modules\server\models\traits;

use hipanel\modules\server\forms\AssignHubsForm;
use hipanel\modules\server\forms\AssignSwitchesForm;
use hipanel\modules\server\models\AssignSwitchInterface;
use hipanel\modules\server\models\Binding;
use Yii;
use yii\base\InvalidConfigException;

trait AssignSwitchTrait
{
    /**
     * List of switch types
     * Example: ['net', 'kvm', 'pdu', 'rack', 'console'].
     *
     * @var array
     */
    protected $switchVariants = [];

    /**
     * @param AssignSwitchInterface $originalModel
     *
     * @return AssignSwitchInterface
     */
    public static function fromOriginalModel(AssignSwitchInterface $originalModel): AssignSwitchInterface
    {
        $attributes = array_merge($originalModel->getAttributes(), []);
        $model = new static(['scenario' => 'default']);
        foreach ($originalModel->bindings as $binding) {
            $attribute = $binding->typeWithNo . '_id';
            if ($model->hasAttribute($attribute)) {
                $attributes[$binding->typeWithNo . '_id'] = $binding->switch_id;
                $attributes[$binding->typeWithNo . '_port'] = $binding->port;
            }
        }
        $model->setAttributes($attributes);

        return $model;
    }

    public function defaultSwitchRules(): array
    {
        $variantIds = [];
        $variantPorts = [];
        foreach ($this->getSwitchVariants() as $variant) {
            $variantIds[] = $variant . '_id';
            $variantPorts[] = $variant . '_port';
        }

        return [
            [['id'], 'required'],
            [$variantIds, 'integer'],
            [$variantPorts, 'string'],
        ];
    }

    /**
     * For compatibility with [[hiqdev\hiart\Collection]].
     *
     * @param $defaultScenario
     * @param array $data
     * @param array $options
     *
     * @return mixed
     */
    public function batchQuery($defaultScenario, $data = [], array $options = [])
    {
        $map = [
            'update' => 'assign-hubs',
        ];
        $scenario = isset($map[$defaultScenario]) ? $map[$defaultScenario] : $defaultScenario;

        return parent::batchQuery($scenario, $data, $options);
    }

    /**
     * This method decides which `assigns` will be offered in the form based on the type of the current model
     *
     * @return array
     */
    public function getSwitchVariants(): array
    {
        $map = [
            'rack' => ['location'],
            'location' => ['location'],
        ];
        /** @var AssignSwitchesForm|AssignHubsForm $this */
        if ($this instanceof AssignSwitchesForm && isset($map[$this->type])) {
            return $map[$this->type];
        }

        return $this->switchVariants;
    }

    /**
     * Added to model's rules list of switch pairs.
     *
     * @return array
     * @throws InvalidConfigException
     */
    protected function generateUniqueValidators(): array
    {
        if (empty($this->switchVariants)) {
            throw new InvalidConfigException('Please specify `switchVariants` array to use AssignSwitchTrait::generateUniqueValidators()');
        }
        $rules = [];

        foreach ($this->getSwitchVariants() as $variant) {
            $rules[] = [
                [$variant . '_port'],
                function ($attribute, $params, $validator) use ($variant) {
                    if ($this->{$attribute} && $this->{$variant . '_id'}) {
                        $query = Binding::find();
                        $query->andWhere(['port' => $this->{$attribute}]);
                        $query->andWhere(['switch_id' => $this->{$variant . '_id'}]);
                        $query->andWhere(['ne', 'base_device_id', $this->id]);
                        /** @var Binding[] $bindings */
                        $bindings = $query->all();
                        if (!empty($bindings)) {
                            $binding = reset($bindings);
                            $this->addError($attribute, Yii::t('hipanel:server', '{switch}::{port} already taken by {device}', [
                                'switch' => $binding->switch_name,
                                'port' => $binding->port,
                                'device' => $binding->device_name,
                            ]));
                        }
                    }
                },
            ];
        }

        return $rules;
    }
}
