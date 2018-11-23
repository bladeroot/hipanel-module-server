<?php

use hipanel\helpers\Url;
use hipanel\modules\server\helpers\ServerSort;
use hipanel\modules\server\widgets\ServerNameBadge;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

/**
 * @var \yii\web\View
 * @var \hipanel\modules\server\models\Server $model
 */
$models = ServerSort::byServerName()->values($models);
?>

<div class="row">
    <?php $form = ActiveForm::begin([
        'id' => 'bulk-set-note',
        'action' => Url::toRoute('@server/set-label'),
        'enableAjaxValidation' => true,
        'validateOnBlur' => true,
        'validationUrl' => Url::toRoute(['validate-form', 'scenario' => 'set-label']),
    ]); ?>

    <div class="col-md-12" style="margin-top: 15pt;">
        <?php foreach ($models as $model) : ?>
            <div class="row">
                <div class="col-md-5 text-right" style="line-height: 34px;">
                    <?= Html::activeHiddenInput($model, "[$model->id]id") ?>
                    <?= ServerNameBadge::widget(['model' => $model]) ?>
                </div>
                <div class="col-md-7">
                    <?= $form->field($model, "[$model->id]label")->textInput([
                        'ref' => 'label-input',
                    ])->label(false) ?>
                </div>
            </div>
        <?php endforeach; ?>

        <hr>
        <?= Html::submitButton(Yii::t('hipanel:server', 'Set notes'), ['class' => 'btn btn-success', 'id' => 'save-button']) ?>
        <?php ActiveForm::end() ?>
    </div>
</div>

<?= \hipanel\widgets\BulkAssignmentFieldsLinker::widget([
    'inputSelectors' => ['input[ref=label-input]'],
]) ?>
