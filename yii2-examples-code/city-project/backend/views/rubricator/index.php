<?php

use yii\web\JsExpression;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use kartik\select2\Select2;
use backend\models\BObject;
use backend\models\Listing;

$this->title = 'Рубрикатор';
$this->params['breadcrumbs'][] = $this->title;

$this->registerJsFile('@web/js/rubricator.js', ['depends' => [yii\web\JqueryAsset::class]]);

?>
<div class="row">
    <div class="col-sm-6">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Аренда апартаментов</h3>
            </div>
            <div class="box-body">
                <?= Select2::widget([
                    'name' => 'complex_id',
                    'options' => [
                        'placeholder' => 'Выберите...',
                        'multiple' => true,
                        'data-type' => BObject::TYPE_APART,
                        'data-listing-type' => Listing::TYPE_RENT
                    ],
                    'value' => isset($data[BObject::TYPE_APART][Listing::TYPE_RENT]) ? ArrayHelper::getColumn($data[BObject::TYPE_APART][Listing::TYPE_RENT], 'complex.id') : '',
                    'initValueText' => isset($data[BObject::TYPE_APART][Listing::TYPE_RENT]) ? ArrayHelper::getColumn($data[BObject::TYPE_APART][Listing::TYPE_RENT], 'complex.name') : '',
                    'pluginOptions' => [
                        'allowClear' => true,
                        'ajax' => [
                            'url' => Url::to(['complex/get-json']),
                            'dataType' => 'json',
                            'data' => new JsExpression('function(params) {
                                return {q:params.term};
                            }'),
                            'processResults' => new JsExpression('function(data) {
                                return {results:data.items};
                            }'),
                        ],
                    ]
                ]) ?>
                <br>

                <table class="table table-bordered<?= !isset($data[BObject::TYPE_APART][Listing::TYPE_RENT]) ? ' d-none' : '' ?>">
                    <thead>
                        <tr>
                            <th>Башня</th>
                            <th>Цена</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (isset($data[BObject::TYPE_APART][Listing::TYPE_RENT])) : ?>
                        <?php foreach ($data[BObject::TYPE_APART][Listing::TYPE_RENT] as $row) : ?>
                            <tr data-id="<?= $row['id'] ?>">
                                <td><?= $row['complex']['name'] ?></td>
                                <td>
                                    <select name="show_price">
                                        <option value="1"<?= $row['show_price'] ? ' selected' : '' ?>>Показывать</option>
                                        <option value="0"<?= !$row['show_price'] ? ' selected' : '' ?>>По запросу</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Аренда офисов</h3>
            </div>
            <div class="box-body">
                <?= Select2::widget([
                    'name' => 'complex_id',
                    'options' => [
                        'placeholder' => 'Выберите...',
                        'multiple' => true,
                        'data-type' => BObject::TYPE_OFFICE,
                        'data-listing-type' => Listing::TYPE_RENT
                    ],
                    'value' => isset($data[BObject::TYPE_OFFICE][Listing::TYPE_RENT]) ? ArrayHelper::getColumn($data[BObject::TYPE_OFFICE][Listing::TYPE_RENT], 'complex.id') : '',
                    'initValueText' => isset($data[BObject::TYPE_OFFICE][Listing::TYPE_RENT]) ? ArrayHelper::getColumn($data[BObject::TYPE_OFFICE][Listing::TYPE_RENT], 'complex.name') : '',
                    'pluginOptions' => [
                        'allowClear' => true,
                        'ajax' => [
                            'url' => Url::to(['complex/get-json']),
                            'dataType' => 'json',
                            'data' => new JsExpression('function(params) {
                                return {q:params.term};
                            }'),
                            'processResults' => new JsExpression('function(data) {
                                return {results:data.items};
                            }'),
                        ],
                    ]
                ]) ?>
                <br>

                <table class="table table-bordered<?= !isset($data[BObject::TYPE_OFFICE][Listing::TYPE_RENT]) ? ' d-none' : '' ?>">
                    <thead>
                        <tr>
                            <th>Башня</th>
                            <th>Цена</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (isset($data[BObject::TYPE_OFFICE][Listing::TYPE_RENT])) : ?>
                        <?php foreach ($data[BObject::TYPE_OFFICE][Listing::TYPE_RENT] as $row) : ?>
                            <tr data-id="<?= $row['id'] ?>">
                                <td><?= $row['complex']['name'] ?></td>
                                <td>
                                    <select name="show_price">
                                        <option value="1"<?= $row['show_price'] ? ' selected' : '' ?>>Показывать</option>
                                        <option value="0"<?= !$row['show_price'] ? ' selected' : '' ?>>По запросу</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-6">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Продажа апартаментов</h3>
            </div>
            <div class="box-body">
                <?= Select2::widget([
                    'name' => 'complex_id',
                    'options' => [
                        'placeholder' => 'Выберите...',
                        'multiple' => true,
                        'data-type' => BObject::TYPE_APART,
                        'data-listing-type' => Listing::TYPE_SALE
                    ],
                    'value' => isset($data[BObject::TYPE_APART][Listing::TYPE_SALE]) ? ArrayHelper::getColumn($data[BObject::TYPE_APART][Listing::TYPE_SALE], 'complex.id') : '',
                    'initValueText' => isset($data[BObject::TYPE_APART][Listing::TYPE_SALE]) ? ArrayHelper::getColumn($data[BObject::TYPE_APART][Listing::TYPE_SALE], 'complex.name') : '',
                    'pluginOptions' => [
                        'allowClear' => true,
                        'ajax' => [
                            'url' => Url::to(['complex/get-json']),
                            'dataType' => 'json',
                            'data' => new JsExpression('function(params) {
                                return {q:params.term};
                            }'),
                            'processResults' => new JsExpression('function(data) {
                                return {results:data.items};
                            }'),
                        ],
                    ]
                ]) ?>
                <br>

                <table class="table table-bordered<?= !isset($data[BObject::TYPE_APART][Listing::TYPE_SALE]) ? ' d-none' : '' ?>">
                    <thead>
                        <tr>
                            <th>Башня</th>
                            <th>Цена</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (isset($data[BObject::TYPE_APART][Listing::TYPE_SALE])) : ?>
                        <?php foreach ($data[BObject::TYPE_APART][Listing::TYPE_SALE] as $row) : ?>
                            <tr data-id="<?= $row['id'] ?>">
                                <td><?= $row['complex']['name'] ?></td>
                                <td>
                                    <select name="show_price">
                                        <option value="1"<?= $row['show_price'] ? ' selected' : '' ?>>Показывать</option>
                                        <option value="0"<?= !$row['show_price'] ? ' selected' : '' ?>>По запросу</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Продажа офисов</h3>
            </div>
            <div class="box-body">
                <?= Select2::widget([
                    'name' => 'complex_id',
                    'options' => [
                        'placeholder' => 'Выберите...',
                        'multiple' => true,
                        'data-type' => BObject::TYPE_OFFICE,
                        'data-listing-type' => Listing::TYPE_SALE
                    ],
                    'value' => isset($data[BObject::TYPE_OFFICE][Listing::TYPE_SALE]) ? ArrayHelper::getColumn($data[BObject::TYPE_OFFICE][Listing::TYPE_SALE], 'complex.id') : '',
                    'initValueText' => isset($data[BObject::TYPE_OFFICE][Listing::TYPE_SALE]) ? ArrayHelper::getColumn($data[BObject::TYPE_OFFICE][Listing::TYPE_SALE], 'complex.name') : '',
                    'pluginOptions' => [
                        'allowClear' => true,
                        'ajax' => [
                            'url' => Url::to(['complex/get-json']),
                            'dataType' => 'json',
                            'data' => new JsExpression('function(params) {
                                return {q:params.term};
                            }'),
                            'processResults' => new JsExpression('function(data) {
                                return {results:data.items};
                            }'),
                        ],
                    ]
                ]) ?>
                <br>

                <table class="table table-bordered<?= !isset($data[BObject::TYPE_OFFICE][Listing::TYPE_SALE]) ? ' d-none' : '' ?>">
                    <thead>
                        <tr>
                            <th>Башня</th>
                            <th>Цена</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (isset($data[BObject::TYPE_OFFICE][Listing::TYPE_SALE])) : ?>
                        <?php foreach ($data[BObject::TYPE_OFFICE][Listing::TYPE_SALE] as $row) : ?>
                            <tr data-id="<?= $row['id'] ?>">
                                <td><?= $row['complex']['name'] ?></td>
                                <td>
                                    <select name="show_price">
                                        <option value="1"<?= $row['show_price'] ? ' selected' : '' ?>>Показывать</option>
                                        <option value="0"<?= !$row['show_price'] ? ' selected' : '' ?>>По запросу</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>