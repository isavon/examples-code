<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;
use yii\bootstrap\ActiveForm;
use common\models\User;
use kartik\select2\Select2;
use backend\models\ListingSearch;
use backend\models\BObjectSearch;

$this->title = 'Disposal Dashboard';
$searchObjectClass  = StringHelper::basename(BObjectSearch::class);
$searchListingClass = StringHelper::basename(ListingSearch::class);

$this->registerCssFile('//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic')
?>
<?php $form = ActiveForm::begin(['method' => 'get']) ?>
<div class="row d-flex">
    <div class="col-sm-4">
        <label class="control-label d-block" for="disposaldashboardform-datefrom">Диапазон дат</label>

        <?= $form->field($model, 'dateFrom', [
            'template' => '{input}',
            'options' => ['class' => 'd-inline'],
        ])->input('date') ?>
        <span class="text-center d-inline" style="width: 3%"> - </span>
        <?= $form->field($model, 'dateTo', [
            'template' => '{input}',
            'options' => ['class' => 'd-inline'],
        ])->input('date') ?>
    </div>

    <div class="col-sm-4">
        <?= $form->field($model, 'userId')->widget(Select2::class, [
            'data' => $model->disposals,
            'options' => [
                'placeholder' => 'Все'
            ],
            'pluginOptions' => [
                'allowClear' => true,
            ],
        ]) ?>
    </div>
    <div class="col-sm-4 d-flex align-items-center">
        <?= Html::submitButton(Yii::t('app', 'Найти'), ['class' => 'btn btn-success btn-lg btn-flat']) ?>
    </div>
</div>
<?php ActiveForm::end() ?>

<div class="box">
    <div class="box-header">
        <h3 class="box-title">Уникальные собственники</h3>
    </div>
    <?php if (count($data['w1']) > 0) : ?>
    <div class="box-body table-responsive no-padding">
        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <th>Диспозл</th>
                    <th class="text-center">Всего компаний, шт.</th>
                    <th class="text-center">Высокая мотивация, шт.</th>
                    <th class="text-center">Средняя мотивация, шт.</th>
                    <th class="text-center">Низкая мотивация, шт.</th>
                    <th class="text-center">Мотивация не указана, шт.</th>
                </tr>
            </thead>
            <tbody>
            <?php
                $total = [];
                $keys  = ['total', 'high', 'middle', 'low', 'not_set'];
            foreach ($keys as $key) {
                $total[$key] = 0;
            }
            ?>
            <?php foreach ($data['w1'] as $row) : ?>
                <tr>
                    <td><?= $row['name'] ?></td>
                    <td class="text-center"><?= $row['total'] > 0 ? Html::a(number_format($row['total'], 0, '.', ' '), [
                        'object/index',
                        $searchObjectClass . '[user_id][]' => $row['user_id'],
                        $searchObjectClass . '[unique_companies]' => 1,
                    ]) : $row['total'] ?></td>
                    <td class="text-center"><?= $row['high'] > 0 ? Html::a(number_format($row['high'], 0, '.', ' '), [
                        'object/index',
                        $searchObjectClass . '[user_id][]' => $row['user_id'],
                        $searchObjectClass . '[unique_companies_high]' => 1,
                    ]) : $row['high'] ?></td>
                    <td class="text-center"><?= $row['middle'] > 0 ? Html::a(number_format($row['middle'], 0, '.', ' '), [
                        'object/index',
                        $searchObjectClass . '[user_id][]' => $row['user_id'],
                        $searchObjectClass . '[unique_companies_middle]' => 1,
                    ]) : $row['middle'] ?></td>
                    <td class="text-center"><?= $row['low'] > 0 ? Html::a(number_format($row['low'], 0, '.', ' '), [
                        'object/index',
                        $searchObjectClass . '[user_id][]' => $row['user_id'],
                        $searchObjectClass . '[unique_companies_low]' => 1,
                    ]) : $row['low'] ?></td>
                    <td class="text-center"><?= $row['not_set'] > 0 ? Html::a(number_format($row['not_set'], 0, '.', ' '), [
                        'object/index',
                        $searchObjectClass . '[user_id][]' => $row['user_id'],
                        $searchObjectClass . '[unique_companies_not_set]' => 1,
                    ]) : $row['not_set'] ?></td>
                </tr>
                <?php foreach ($total as $key => $val) {
                    $total[$key] += $row[$key];
                } ?>
            <?php endforeach ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>Всего</th>
                <?php foreach ($total as $row) : ?>
                    <th class="text-center"><?= number_format($row, 0, '.', ' ') ?></th>
                <?php endforeach ?>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else : ?>
    <div class="box-body">Данные не найдены</div>
    <?php endif ?>
</div>

<div class="box">
    <div class="box-header">
        <h3 class="box-title">Объекты с активными листингами</h3>
    </div>
    <?php if (count($data['w2']) > 0) : ?>
    <div class="box-body table-responsive no-padding">
        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <th>Диспозл</th>
                    <th class="text-center">Всего объектов с Активными листингами, шт.</th>
                    <th class="text-center">С Высокой, шт.</th>
                    <th class="text-center">Со Средней, шт.</th>
                    <th class="text-center">С Низкой, шт.</th>
                    <th class="text-center">Мотивация не указана, шт.</th>
                    <th class="text-center">Всего объектов с Активными листингами, м<sup>2</sup></th>
                    <th class="text-center">С Высокой, м<sup>2</sup></th>
                    <th class="text-center">Со Средней, м<sup>2</sup></th>
                    <th class="text-center">С Низкой, м<sup>2</sup></th>
                    <th class="text-center">Мотивация не указана, м<sup>2</sup></th>
                </tr>
            </thead>
            <tbody>
            <?php
                $total = [];
                $keys  = ['total', 'high', 'middle', 'low', 'not_set', 'area_total', 'area_high', 'area_middle', 'area_low', 'area_not_set'];
            foreach ($keys as $key) {
                $total[$key] = 0;
            }
            ?>
            <?php foreach ($data['w2'] as $row) : ?>
                <tr>
                    <td><?= $row['name'] ?></td>
                    <td class="text-center"><?= $row['total'] > 0 ? Html::a(number_format($row['total'], 0, '.', ' '), [
                        'object/index',
                        $searchObjectClass . '[user_id][]' => $row['user_id'],
                        $searchObjectClass . '[listing_active]' => 1,
                    ]) : $row['total'] ?></td>
                    <td class="text-center"><?= $row['high'] > 0 ? Html::a(number_format($row['high'], 0, '.', ' '), [
                        'object/index',
                        $searchObjectClass . '[user_id][]' => $row['user_id'],
                        $searchObjectClass . '[object_high_motivation]' => 1,
                    ]) : $row['high'] ?></td>
                    <td class="text-center"><?= $row['middle'] > 0 ? Html::a(number_format($row['middle'], 0, '.', ' '), [
                        'object/index',
                        $searchObjectClass . '[user_id][]' => $row['user_id'],
                        $searchObjectClass . '[object_middle_motivation]' => 1,
                    ]) : $row['middle'] ?></td>
                    <td class="text-center"><?= $row['low'] > 0 ? Html::a(number_format($row['low'], 0, '.', ' '), [
                        'object/index',
                        $searchObjectClass . '[user_id][]' => $row['user_id'],
                        $searchObjectClass . '[object_low_motivation]' => 1,
                    ]) : $row['low'] ?></td>
                    <td class="text-center"><?= $row['not_set'] > 0 ? Html::a(number_format($row['not_set'], 0, '.', ' '), [
                        'object/index',
                        $searchObjectClass . '[user_id][]' => $row['user_id'],
                        $searchObjectClass . '[object_not_set_motivation]' => 1,
                    ]) : $row['not_set'] ?></td>
                    <td class="text-center"><?= number_format($row['area_total'], 0, '.', ' ') ?></td>
                    <td class="text-center"><?= number_format($row['area_high'], 0, '.', ' ') ?></td>
                    <td class="text-center"><?= number_format($row['area_middle'], 0, '.', ' ') ?></td>
                    <td class="text-center"><?= number_format($row['area_low'], 0, '.', ' ') ?></td>
                    <td class="text-center"><?= number_format($row['area_not_set'], 0, '.', ' ') ?></td>
                </tr>
                <?php foreach ($total as $key => $val) {
                    $total[$key] += $row[$key];
                } ?>
            <?php endforeach ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>Всего</th>
                <?php foreach ($total as $row) : ?>
                    <th class="text-center"><?= number_format($row, 0, '.', ' ') ?></th>
                <?php endforeach ?>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else : ?>
    <div class="box-body">Данные не найдены</div>
    <?php endif ?>
</div>

<div class="box">
    <div class="box-header">
        <h3 class="box-title">Активные листинги</h3>
    </div>
    <?php if (count($data['w3']) > 0) : ?>
    <div class="box-body table-responsive no-padding">
        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <th>Дисполз</th>
                    <th class="text-center">Всего Активных листингов, шт.</th>
                    <th class="text-center">С Высокой, шт.</th>
                    <th class="text-center">Со Средней, шт.</th>
                    <th class="text-center">С Низкой, шт.</th>
                    <th class="text-center">Мотивация не указана, шт.</th>
                    <th class="text-center">Всего Активных листингов, м<sup>2</sup></th>
                    <th class="text-center">С Высокой, м<sup>2</sup></th>
                    <th class="text-center">Со Средней, м<sup>2</sup></th>
                    <th class="text-center">С Низкой, м<sup>2</sup></th>
                    <th class="text-center">Мотивация не указана, м<sup>2</sup></th>
                </tr>
            </thead>
            <tbody>
            <?php
                $total = [];
                $keys  = ['total', 'high', 'middle', 'low', 'not_set', 'area_total', 'area_high', 'area_middle', 'area_low', 'area_not_set'];
            foreach ($keys as $key) {
                $total[$key] = 0;
            }
            ?>
            <?php foreach ($data['w3'] as $row) : ?>
                <tr>
                    <td><?= $row['name'] ?></td>
                    <td class="text-center"><?= $row['total'] > 0 ? Html::a(number_format($row['total'], 0, '.', ' ') ?: 0, [
                        'listing/index',
                        $searchListingClass . '[user_id][]' => $row['user_id'],
                    ]) : $row['total'] ?></td>
                    <td class="text-center"><?= $row['high'] > 0 ? Html::a(number_format($row['high'], 0, '.', ' ') ?: 0, [
                        'listing/index',
                        $searchListingClass . '[user_id][]' => $row['user_id'],
                        $searchListingClass . '[listing_high_motivation]' => 1,
                    ]) : $row['high'] ?></td>
                    <td class="text-center"><?= $row['middle'] > 0 ? Html::a(number_format($row['middle'], 0, '.', ' ') ?: 0, [
                        'listing/index',
                        $searchListingClass . '[user_id][]' => $row['user_id'],
                        $searchListingClass . '[listing_middle_motivation]' => 1,
                    ]) : $row['middle'] ?></td>
                    <td class="text-center"><?= $row['low'] > 0 ? Html::a(number_format($row['low'], 0, '.', ' ') ?: 0, [
                        'listing/index',
                        $searchListingClass . '[user_id][]' => $row['user_id'],
                        $searchListingClass . '[listing_low_motivation]' => 1,
                    ]) : $row['low'] ?></td>
                    <td class="text-center"><?= $row['not_set'] > 0 ? Html::a(number_format($row['not_set'], 0, '.', ' ') ?: 0, [
                        'listing/index',
                        $searchListingClass . '[user_id][]' => $row['user_id'],
                        $searchListingClass . '[listing_not_set_motivation]' => 1,
                    ]) : $row['not_set'] ?></td>
                    <td class="text-center"><?= number_format($row['area_total'], 0, '.', ' ') ?: 0 ?></td>
                    <td class="text-center"><?= number_format($row['area_high'], 0, '.', ' ') ?: 0 ?></td>
                    <td class="text-center"><?= number_format($row['area_middle'], 0, '.', ' ') ?: 0 ?></td>
                    <td class="text-center"><?= number_format($row['area_low'], 0, '.', ' ') ?: 0 ?></td>
                    <td class="text-center"><?= number_format($row['area_not_set'], 0, '.', ' ') ?: 0 ?></td>
                </tr>
                <?php foreach ($total as $key => $val) {
                    $total[$key] += $row[$key];
                } ?>
            <?php endforeach ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>Всего</th>
                <?php foreach ($total as $row) : ?>
                    <th class="text-center"><?= number_format($row, 0, '.', ' ') ?: 0 ?></th>
                <?php endforeach ?>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else : ?>
    <div class="box-body">Данные не найдены</div>
    <?php endif ?>
</div>

<div class="box">
    <div class="box-header">
        <h3 class="box-title">Новые объекты/листинги</h3>
    </div>
    <?php if (count($data['w4']) > 0) : ?>
    <div class="box-body table-responsive no-padding">
        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <th>Диспозл</th>
                    <th class="text-center">Новые объекты, шт.</th>
                    <th class="text-center">Новые листинги, шт.</th>
                    <th class="text-center">Новые объекты, м<sup>2</sup></th>
                    <th class="text-center">Новые листинги, м<sup>2</sup></th>
                </tr>
            </thead>
            <tbody>
            <?php
                $total = [];
                $keys  = ['objects', 'listings', 'objects_m2', 'listings_m2'];
            foreach ($keys as $key) {
                $total[$key] = 0;
            }
            ?>
            <?php foreach ($data['w4'] as $row) : ?>
                <tr>
                    <td><?= $row['username'] ?></td>
                    <td class="text-center"><?= $row['objects'] > 0 ? Html::a(number_format($row['objects'], 0, '.', ' '), [
                        'object/index',
                        $searchObjectClass . '[user_id][]' => $row['user_id'],
                        $searchObjectClass . '[created_at_from]' => $model->dateFrom,
                        $searchObjectClass . '[created_at_to]' => $model->dateTo,
                    ]) : $row['objects'] ?></td>
                    <td class="text-center"><?= $row['listings'] > 0 ? Html::a(number_format($row['listings'], 0, '.', ' '), [
                        'listing/index',
                        $searchListingClass . '[activity]' => '',
                        $searchListingClass . '[user_id][]' => $row['user_id'],
                        $searchListingClass . '[created_at]' => $model->dateFrom . ',' . $model->dateTo,
                    ]) : $row['listings'] ?></td>
                    <td class="text-center"><?= number_format($row['objects_m2'], 0, '.', ' ') ?></td>
                    <td class="text-center"><?= number_format($row['listings_m2'], 0, '.', ' ') ?></td>
                </tr>
                <?php foreach ($total as $key => $val) {
                    $total[$key] += $row[$key];
                } ?>
            <?php endforeach ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>Всего</th>
                <?php foreach ($total as $row) : ?>
                    <th class="text-center"><?= number_format($row, 0, '.', ' ') ?></th>
                <?php endforeach ?>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else : ?>
    <div class="box-body">Данные не найдены</div>
    <?php endif ?>
</div>

<div class="row">
    <div class="col-sm-4">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Актуализация листингов</h3>
            </div>
            <?php if (count($data['w5']) > 0) : ?>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>Диспозл</th>
                            <th class="text-center">Актуализировано листингов, шт.</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $c1 = 0 ?>
                    <?php foreach ($data['w5'] as $row) : ?>
                        <tr>
                            <td><?= $row['name'] ?></td>
                            <td class="text-center"><?= $row['listings'] > 0 ? Html::a(number_format($row['listings'], 0, '.', ' '), [
                                'listing/index',
                                $searchListingClass . '[activity]' => '',
                                $searchListingClass . '[user_id][]' => $row['user_id'],
                                $searchListingClass . '[disposal_date_from]' => $model->dateFrom,
                                $searchListingClass . '[disposal_date_to]' => $model->dateTo,
                                $searchListingClass . '[listings_updated]' => 1,
                            ]) : $row['listings'] ?></td>
                        </tr>
                        <?php $c1 += $row['listings'] ?>
                    <?php endforeach ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Всего</th>
                            <th class="text-center"><?= number_format($c1, 0, '.', ' ') ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else : ?>
            <div class="box-body">Данные не найдены</div>
            <?php endif ?>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Фотографии</h3>
            </div>
            <?php if (count($data['w7']) > 0) : ?>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>Диспозл</th>
                            <th class="text-center">Листингов с новыми фотографиями, шт.</th>
                            <th class="text-center">Листингов с новыми планировками, шт.</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $c1 = $c2 = 0 ?>
                    <?php foreach ($data['w7'] as $userId => $row) : ?>
                        <tr>
                            <td><?= $row['name'] ?></td>
                            <td class="text-center"><?= $row['photos'] > 0 ? Html::a(number_format($row['photos'], 0, '.', ' '), [
                                'listing/index',
                                $searchListingClass . '[activity]' => '',
                                $searchListingClass . '[user_id][]' => $userId,
                                $searchListingClass . '[disposal_date_from]' => $model->dateFrom,
                                $searchListingClass . '[disposal_date_to]' => $model->dateTo,
                                $searchListingClass . '[listings_with_new_photos]' => 1,
                            ]) : $row['photos'] ?></td>
                            <td class="text-center"><?= $row['plans'] > 0 ? Html::a(number_format($row['plans'], 0, '.', ' '), [
                                'listing/index',
                                $searchListingClass . '[activity]' => '',
                                $searchListingClass . '[user_id][]' => $userId,
                                $searchListingClass . '[disposal_date_from]' => $model->dateFrom,
                                $searchListingClass . '[disposal_date_to]' => $model->dateTo,
                                $searchListingClass . '[listings_with_new_plans]' => 1,
                            ]) : $row['plans'] ?></td>
                        </tr>
                        <?php
                            $c1 += $row['photos'];
                            $c2 += $row['plans'];
                        ?>
                    <?php endforeach ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Всего</th>
                            <th class="text-center"><?= number_format($c1, 0, '.', ' ') ?></th>
                            <th class="text-center"><?= number_format($c2, 0, '.', ' ') ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else : ?>
            <div class="box-body">Данные не найдены</div>
            <?php endif ?>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Листинги на доработке</h3>
            </div>
            <?php if (count($data['w9']) > 0) : ?>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>Диспозл</th>
                            <th class="text-center">Листингов на доработке, шт.</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $c1 = 0 ?>
                    <?php foreach ($data['w9'] as $row) : ?>
                        <tr>
                            <td><?= $row['name'] ?></td>
                            <td class="text-center"><?= $row['listings'] > 0 ? Html::a(number_format($row['listings'], 0, '.', ' '), [
                                'listing/index',
                                $searchListingClass . '[user_id][]' => $row['user_id'],
                                $searchListingClass . '[onRevision]' => 1,
                            ]) : $row['listings'] ?></td>
                        </tr>
                        <?php $c1 += $row['listings'] ?>
                    <?php endforeach ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Всего</th>
                            <th class="text-center"><?= number_format($c1, 0, '.', ' ') ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else : ?>
            <div class="box-body">Данные не найдены</div>
            <?php endif ?>
        </div>
    </div>
</div>

<div class="box">
    <div class="box-header">
        <h3 class="box-title">Активность Диспозла</h3>
    </div>
    <?php if (count($data['w6']) > 0) : ?>
    <div class="box-body table-responsive no-padding">
        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <th>Диспозл</th>
                    <th class="text-center">Листингов  со сниженной ценой, шт.</th>
                    <th class="text-center">Листингов с полем "торг до", шт.</th>
                    <th class="text-center">Листингов с уменьшением "торг до", шт.</th>
                    <th class="text-center">Договоров подписано, шт.</th>
                </tr>
            </thead>
            <tbody>
            <?php
                $total = [];
                $keys  = ['cost_changes', 'bargaining_set', 'bargain_changes', 'contract_set'];
            foreach ($keys as $key) {
                $total[$key] = 0;
            }
            ?>
            <?php foreach ($data['w6'] as $row) : ?>
                <tr>
                    <td><?= $row['name'] ?></td>
                    <td class="text-center"><?= $row['cost_changes'] > 0 ? Html::a(number_format($row['cost_changes'], 0, '.', ' '), [
                        'listing/index',
                        $searchListingClass . '[activity]' => '',
                        $searchListingClass . '[user_id][]' => $row['user_id'],
                        $searchListingClass . '[disposal_date_from]' => $model->dateFrom,
                        $searchListingClass . '[disposal_date_to]' => $model->dateTo,
                        $searchListingClass . '[history_cost_down]' => 1,
                    ]) : $row['cost_changes'] ?></td>
                    <td class="text-center"><?= $row['bargaining_set'] > 0 ? Html::a(number_format($row['bargaining_set'], 0, '.', ' '), [
                        'listing/index',
                        $searchListingClass . '[activity]' => '',
                        $searchListingClass . '[user_id][]' => $row['user_id'],
                        $searchListingClass . '[disposal_date_from]' => $model->dateFrom,
                        $searchListingClass . '[disposal_date_to]' => $model->dateTo,
                        $searchListingClass . '[history_bargain_set]' => 1,
                    ]) : $row['bargaining_set'] ?></td>
                    <td class="text-center"><?= $row['bargain_changes'] > 0 ? Html::a(number_format($row['bargain_changes'], 0, '.', ' '), [
                        'listing/index',
                        $searchListingClass . '[activity]' => '',
                        $searchListingClass . '[user_id][]' => $row['user_id'],
                        $searchListingClass . '[disposal_date_from]' => $model->dateFrom,
                        $searchListingClass . '[disposal_date_to]' => $model->dateTo,
                        $searchListingClass . '[history_bargain_down]' => 1,
                    ]) : $row['bargain_changes'] ?></td>
                    <td class="text-center"><?= $row['contract_set'] > 0 ? Html::a(number_format($row['contract_set'], 0, '.', ' '), [
                        'listing/index',
                        $searchListingClass . '[activity]' => '',
                        $searchListingClass . '[user_id][]' => $row['user_id'],
                        $searchListingClass . '[disposal_date_from]' => $model->dateFrom,
                        $searchListingClass . '[disposal_date_to]' => $model->dateTo,
                        $searchListingClass . '[history_contract_set]' => 1,
                    ]) : $row['contract_set'] ?></td>
                </tr>
                <?php
                foreach ($total as $key => $val) {
                    $total[$key] += $row[$key];
                }
                ?>
            <?php endforeach ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>Всего</th>
                <?php foreach ($total as $row) : ?>
                    <th class="text-center"><?= number_format($row, 0, '.', ' ') ?></th>
                <?php endforeach ?>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else : ?>
    <div class="box-body">Данные не найдены</div>
    <?php endif ?>
</div>

<div class="box">
    <div class="box-header">
        <h3 class="box-title">Привлекательность листингов</h3>
    </div>
    <?php if (count($data['w8']) > 0) : ?>
    <div class="box-body table-responsive no-padding">
        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <th>Диспозл</th>
                    <th class="text-center">Листингов на Циан, шт.</th>
                    <th class="text-center">Отправлено листингов клиентам, шт.</th>
                </tr>
            </thead>
            <tbody>
            <?php
                $total = [];
                $keys  = ['listings', 'sends'];
            foreach ($keys as $key) {
                $total[$key] = 0;
            }
            ?>
            <?php foreach ($data['w8'] as $row) : ?>
                <tr>
                    <td><?= $row['name'] ?></td>
                    <td class="text-center"><?= $row['listings'] > 0 ? Html::a(number_format($row['listings'], 0, '.', ' '), [
                        'listing/index',
                        $searchListingClass . '[user_id][]' => $row['user_id'],
                        $searchListingClass . '[on_cian]' => 1,
                    ]) : $row['listings'] ?></td>
                    <td class="text-center"><?= $row['sends'] > 0 ? Html::a(number_format($row['sends'], 0, '.', ' '), [
                        'listing/index',
                        $searchListingClass . '[user_id][]' => $row['user_id'],
                        $searchListingClass . '[sends_to_clients]' => 1,
                        $searchListingClass . '[disposal_date_from]' => $model->dateFrom,
                        $searchListingClass . '[disposal_date_to]' => $model->dateTo,
                    ]) : $row['sends'] ?></td>
                </tr>
                <?php
                foreach ($total as $key => $val) {
                    $total[$key] += $row[$key];
                }
                ?>
            <?php endforeach ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>Всего</th>
                    <th class="text-center"><?= Html::a(number_format($total['listings'], 0, '.', ' '), [
                        'listing/index',
                        $searchListingClass . '[all_disposals]' => 1,
                        $searchListingClass . '[on_cian]' => 1,
                    ]) ?></th>
                    <th class="text-center"><?= Html::a(number_format($total['sends'], 0, '.', ' '), [
                        'listing/index',
                        $searchListingClass . '[sends_all_disposals]' => 1,
                        $searchListingClass . '[sends_to_clients]' => 1,
                        $searchListingClass . '[disposal_date_from]' => $model->dateFrom,
                        $searchListingClass . '[disposal_date_to]' => $model->dateTo,
                    ]) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else : ?>
    <div class="box-body">Данные не найдены</div>
    <?php endif ?>
</div>