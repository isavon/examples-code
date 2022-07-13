<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;
use yii\widgets\Pjax;
use yii\grid\GridView;
use yii\web\JqueryAsset;
use yii\bootstrap\ActiveForm;
use kartik\date\DatePicker;
use kartik\switchinput\SwitchInput;
use backend\models\Ad;
use backend\models\ListingAd;
use backend\models\Currency;
use backend\models\ListingSite;
use backend\models\Listing;
use backend\models\BObject;
use backend\models\LList;

$this->title = 'Ставки cian';
$this->params['breadcrumbs'][] = $this->title;

$no_site = null;
$no_board = null;
$activity = null;

$this->registerCss('
    .info-box-number{font-size:16px}
    .info-box-content h4{font-size:32px}
    .small-box a{color:white}
    #griditems, .wrapper {overflow: unset}
    #griditems .table tbody tr:not(.statistic) td:first-child{cursor:help}
    .statistic span{display:inherit;width:100px}
    .btn-duration{background:none;border:0; padding: 0}
    th {background: #ecf0f5;position:sticky;top:-1px;z-index:2}
    #balance_type {color:#555}
');

$cianVals = Ad::findOne(Ad::CIAN);

$this->registerJsFile('@web/js/basket.js', ['depends' => JqueryAsset::class]);
$this->registerJsFile('@web/js/listing.js', ['depends' => JqueryAsset::class]);
$this->registerJsFile('@web/js/bet-cian.js', ['depends' => JqueryAsset::class]);
?>
<div class="row">
    <div class="col-md-4 col-sm-6 col-xs-12">
        <?php $form = ActiveForm::begin(['method' => 'get']) ?>
        <label class="control-label">Отчётный период</label>
        <?= DatePicker::widget([
            'model' => $model,
            'attribute' => 'dateFrom',
            'attribute2' => 'dateTo',
            'form' => $form,
            'type' => DatePicker::TYPE_RANGE,
            'separator' => 'до',
            'pluginOptions' => [
                'autoclose' => true,
                'format' => 'dd.mm.yyyy'
            ]
        ]) ?>
        <?php ActiveForm::end() ?>
    </div>
    <div class="col-md-1 col-sm-6 col-xs-12">
        <div class="form-group text-center">
            <label class="control-label">&nbsp;</label><br>
            <?= Html::a('Циан Feed', ['listing/cian'], ['target' => '_blank']) ?>
        </div>
    </div>
    <div class="col-md-4 col-sm-6 col-xs-12">
        <div class="row">
            <div class="col-sm-4 col-xs-12">
                <label class="control-label">Выходные</label>
                <?= SwitchInput::widget([
                    'name' => 'cian-weekend',
                    'type' => SwitchInput::CHECKBOX,
                    'value' => $cianVals->weekend,
                    'pluginEvents' => [
                        'switchChange.bootstrapSwitch' => new JsExpression('function() {
                            $.get("/listing/weekend", {id: ' . Ad::CIAN . '});
                        }'),
                    ],
                    'pluginOptions' => [
                        'onColor' => 'success',
                        'offColor' => 'danger',
                    ]
                ]) ?>
            </div>

            <div class="col-sm-4 col-xs-12">
                <label class="control-label">Выходные апартаменты</label>
                <?= SwitchInput::widget([
                    'name' => 'cian-weekend-apart',
                    'type' => SwitchInput::CHECKBOX,
                    'value' => $cianVals->weekend_apart,
                    'pluginEvents' => [
                        'switchChange.bootstrapSwitch' => new JsExpression('function() {
                            $.get("/listing/weekend-apart", {id: ' . Ad::CIAN . '});
                        }'),
                    ],
                    'pluginOptions' => [
                        'onColor' => 'success',
                        'offColor' => 'danger',
                    ]
                ]) ?>
            </div>

            <div class="col-sm-4 col-xs-12">
                <label class="control-label">Ставки 0</label>
                <?= SwitchInput::widget([
                    'name' => 'cian-zero-bet',
                    'type' => SwitchInput::CHECKBOX,
                    'value' => $cianVals->zero_bet,
                    'pluginEvents' => [
                        'switchChange.bootstrapSwitch' => new JsExpression('function() {
                            $.get("/listing/zero-bet", {id: ' . Ad::CIAN . '});
                        }'),
                    ],
                    'pluginOptions' => [
                        'onColor' => 'success',
                        'offColor' => 'danger',
                    ]
                ]) ?>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label">&nbsp;</label><br>
                    <a href='/listing/delete-feed?board=<?= Ad::CIAN ?>&all'>
                        Очистить фид
                        <span class="label label-success"><?=ListingAd::getCountCianListings()?></span>
                    </a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label">&nbsp;</label>
                    <br>
                    <?= $model->lastProcessDate ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="small-box bg-aqua">
            <div class="inner">
                <h3><?= number_format($stat['budget'], 0, '.', ' ') ?> руб.</h3>
                <p>Бюджет</p>
            </div>
            <div class="icon">
                <i class="fa fa-calculator"></i>
            </div>
            <div class="small-box-footer">&nbsp;</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="small-box bg-green">
            <div class="inner">
                <h3><span id="balance"><?= number_format($stat['balance'], 0, '.', ' ') ?></span> руб.</h3>
                <p>Баланс</p>
            </div>
            <div class="icon">
                <i class="fa fa-bar-chart"></i>
            </div>
            <div class="small-box-footer">
                <select id="balance_type">
                <?php foreach (Ad::CIAN_BALANCE_TYPES as $val => $name) : ?>
                    <option value="<?= $val ?>"<?= $model->ad->balance_type == $val ? ' selected' : '' ?>><?= $name ?></option>
                <?php endforeach ?>
                </select>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="small-box bg-yellow">
            <div class="inner">
                <h3>
                    <?= Html::a($stat['lead'], ['get-lead-ids'], ['title' => 'Общие']) ?>/<?= Html::a($stat['targetLead'], ['get-target-lead-ids'], ['title' => 'Целевые']) ?> сделок
                </h3>
                <p>Лиды</p>
            </div>
            <div class="icon">
                <i class="fa fa-male"></i>
            </div>
            <div class="small-box-footer">&nbsp;</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="small-box bg-fuchsia">
            <div class="inner">
                <h3>
                    <span title="Общие"><?= number_format($stat['cpl'], 0, '.', ' ') ?></span>/<span title="Целевые"><?= number_format($stat['cplTarget'], 0, '.', ' ') ?></span> руб.
                </h3>
                <p>CPL</p>
            </div>
            <div class="icon">
                <i class="fa fa-soccer-ball-o"></i>
            </div>
            <div class="small-box-footer">&nbsp;</div>
        </div>
    </div>
</div>
<h4>Сегодня:</h4>
<div class="row">
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <span class="info-box-icon bg-aqua">
                <?= DatePicker::widget([
                    'name' => 'duration',
                    'type' => DatePicker::TYPE_BUTTON,
                    'buttonOptions' => [
                        'class' => 'btn-duration',
                        'label' => '<i class="fa fa-users"></i>'
                    ],
                    'pluginOptions' => [
                        'autoclose' => true,
                    ],
                    'pluginEvents' => [
                        'changeDate' => new JsExpression('function(e) {
                            $.post("/bet-cian/set-duration", {duration: $(this).find("input").val()}, function(response) {
                                $("#duration").text(response.duration);
                                $("#plan").text(response.plan);
                                $("#fact").text(response.fact);
                                $("#rest").text(response.rest);

                                if (response.rest < 0) {
                                    $("#rest").closest("h4").removeClass("text-fuchsia").addClass("text-danger");
                                } else {
                                    $("#rest").closest("h4").removeClass("text-danger").addClass("text-fuchsia");
                                }
                            });
                        }')
                    ]
                ])?>
            </span>
            <div class="info-box-content">
                <div class="info-box-number">Длительность пакета</div>
                <h4 class="text-aqua">
                    <strong><span id="duration"><?= $today['duration'] ?></span> дн.</strong>
                </h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <span class="info-box-icon bg-green">
                <i class="fa fa-money"></i>
            </span>
            <div class="info-box-content">
                <div class="info-box-number">Баланс на день (план)</div>
                <h4 class="text-green"><strong><span id="plan"><?= $today['plan'] ?></span> руб.</strong></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <span class="info-box-icon bg-yellow">
                <i class="fa fa-recycle"></i>
            </span>
            <div class="info-box-content">
                <div class="info-box-number">Баланс на день (факт)</div>
                <h4 class="text-yellow"><strong><span id="fact"><?= $today['fact'] ?></span> руб.</strong></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <span class="info-box-icon bg-fuchsia">
                <i class="fa fa-meh-o"></i>
            </span>
            <div class="info-box-content">
                <div class="info-box-number">Остаток баланса</div>
                <h4 class="<?= $today['rest'] < 0 ? 'text-danger' : 'text-fuchsia' ?>"><strong><span id="rest"><?= $today['rest'] ?></span> руб.</strong></h4>
            </div>
        </div>
    </div>
</div>
<br>

<?php
$towerNames = ArrayHelper::map(backend\models\Tower::find()->all(), 'id', 'name');
?>

<div class="row">
    <div class="col-xs-1">
        <label>Показать:</label>
        <br>
        <?= Html::dropDownList(
            'pagesize',
            (Yii::$app->session->get('pagesize') >= 0) ? Yii::$app->session->get('pagesize') : 20,
            [20 => 20, 50 => 50, 100 => 100, 0 => 'Все'],
            ['id' => 'pagesize', 'class' => 'btn btn-default', 'onchange' => new JsExpression('location.href="/listing/change-page-size?size=" + this.value')]
        );
                                                                ?>
    </div>

    <div class="col-xs-2">
        <label>Показать тарифы:</label>
        <br>
        <?= Html::dropDownList(
            'promo',
            Yii::$app->session->get('promo'),
            ['all' => 'Все'] + ListingAd::$promoOptions[Ad::CIAN],
            ['id' => 'promo', 'class' => 'btn btn-default', 'multiple' => true, 'onchange' => new JsExpression('location.href="/bet-cian/change-promo?promo=" + $(this).val()')]
        ) ?>
    </div>

    <div class="col-xs-2">
        <label>Тип:</label>
        <br>
        <?= Html::dropDownList(
            'type',
            Yii::$app->session->get('type'),
            ['all' => 'Все'] + Listing::$type_ru,
            ['id' => 'type', 'onchange' => new JsExpression('location.href="/bet-cian/change-type?type=" + $(this).val()')]
        ) ?>
    </div>

    <div class="col-xs-2">
        <label>Вид:</label>
        <br>
        <?= Html::dropDownList(
            'layout',
            Yii::$app->session->get('layout'),
            ['all' => 'Все'] + BObject::$type_ru,
            ['id' => 'layout', 'onchange' => new JsExpression('location.href="/bet-cian/change-layout?layout=" + $(this).val()')]
        ) ?>
    </div>

    <div class="col-xs-3">
        <label>Башня:</label>
        <br>
        <?= Html::dropDownList(
            'tower',
            Yii::$app->session->get('tower'),
            ['all' => 'Все'] + $towerNames,
            ['id' => 'tower', 'onchange' => new JsExpression('location.href="/bet-cian/change-tower?tower=" + $(this).val()')]
        ) ?>
    </div>
</div>

<div id="photos" class="hidden"></div>

<?php Pjax::begin(); ?>
<?= GridView::widget([
    'id' => 'griditems',
    'dataProvider' => $dataProvider,
    'rowOptions' => function ($row) use ($model) {
        $options = ['data-idd' => $row->idd];

        if ($row->object->from_builder) {
            $options['style'] = 'background: #98ffcc; border: none;';
        }

        if (in_array($row->id, array_keys($model->cianLists))) {
            $options['class'] = 'danger';
        }

        return $options;
    },
    'columns' => [
        [
            'attribute' => 'idd',
            'content' => function (Listing $model) {
                $options = [];
                if ($this->context->isVisit($model->id)) {
                    $options['style'] = 'color: pink;';
                }
                return Html::a($model->idd, ['listing/view', 'id' => $model->id], $options);
            }
        ],
        [
            'attribute' => 'name',
            'label' => 'Тип',
            'format' => 'raw',
            'value' => function (Listing $model) {
                return '<span class="listing-type" data-type="' . $model->listing_type_id . '">' . Listing::$type_ru[$model->listing_type_id] . '</span>';
            }
        ],
        [
            'attribute' => 'type_id',
            'label' => 'Вид',
            'value' => function (Listing $model) {
                return strtok(BObject::$type_ru[$model->type1], " ");
            }
        ],
        [
            'attribute' => 'listing_type_id',
            'label' => 'Башня',
            'value' => function (Listing $model) use ($towerNames) {
                return $towerNames[$model->object->tower_id];
            }
        ],
        [
            'attribute' => 'area',
            'label' => 'Площадь',
            'contentOptions' => ['class' => 'area'],
            'value' => function ($row) {
                return $row->area1;
            }
        ],
        [
            'attribute' => 'object_id',
            'label' => 'Этаж',
            'value' => function ($row) use ($model) {
                if (!in_array($row->id, array_keys($model->cianLists))) {
                    return $row->object->floor;
                }

                return $model->cianLists[$row->id]['list']->floor;
            }
        ],
        [
            'attribute' => 'cost_m2',
            'label' => 'Стоимость кв.м, ' . $cur_short,
            'format' => 'raw',
            'headerOptions' => ['width' => '10%'],
            'value' => function ($row) use ($model, $cur_short, $listingsCian) {
                if (isset($listingsCian[$row->id])) {
                    $value = $listingsCian[$row->id]->cost_m2 ?: $row->cost_m2;
                    $value = $row->convertCurrency($row->currency_id, 1, $value);
                } else {
                    $value = $model->cianLists[$row->id]['listing']->cost_m2;
                }

                return Html::textInput('cost', number_format($value, 2, '.', ''), ['class' => 'form-control cian-cost-m2']);
            }
        ],
        [
            'attribute' => 'cost',
            'format' => 'raw',
            'label' => 'Стоимость, ' . $cur_short,
            'headerOptions' => ['width' => '10%'],
            'value' => function ($row) use ($model, $cur_short, $listingsCian) {
                if (isset($listingsCian[$row->id])) {
                    $value = $listingsCian[$row->id]->cost ?: $row->cost;
                    $value = $row->convertCurrency($row->currency_id, 1, $value);
                } else {
                    $value = $model->cianLists[$row->id]['listing']->cost;
                }

                return Html::textInput('cost', number_format($value, 2, '.', ''), ['class' => 'form-control cian-cost']);
            }
        ],
        [
            'attribute' => 'bet',
            'label' => 'Ставка',
            'format' => 'raw',
            'headerOptions' => ['width' => 75],
            'value' => function ($row) use ($model, $listingsCian) {
                if (!in_array($row->id, array_keys($model->cianLists))) {
                    $la = $listingsCian[$row->id];

                    $options = [
                        'value' => $row->bet,
                        'class' => '',
                        'id' => $row->id,
                        'link' => '',
                    ];
                } else {
                    $options = [
                        'value' => (int) $model->cianLists[$row->id]['list']->rate,
                        'class' => ' list',
                        'id' => $model->cianLists[$row->id]['list']->id,
                        'link' => Url::to(['listing/list', 'id' => $model->cianLists[$row->id]['list']->id]),
                    ];
                }

                return Html::textInput('bet', $options['value'], ['class' => 'form-control cian-bet' . $options['class'], 'data-id' => $options['id'], 'data-link' => $options['link']]);
            }
        ],
        [
            'attribute' => 'ak_proc',
            'label' => 'Лиды',
            'format' => 'raw',
            'value' => function ($row) use ($model, $stat) {
                $lead = 0;
                $target = 0;

                if (isset($stat['listingLeads'][$row->idd])) {
                    $lead = $stat['listingLeads'][$row->idd]['lead'];
                    $target = $stat['listingLeads'][$row->idd]['targetLead'];
                }

                if (isset($model->cianLists[$row->id]) && isset($stat['listingLeads'][$model->cianLists[$row->id]['list']->id])) {
                    $lead += $stat['listingLeads'][$model->cianLists[$row->id]['list']->id]['lead'];
                    $target += $stat['listingLeads'][$model->cianLists[$row->id]['list']->id]['targetLead'];
                }

                return '<span title="Общие">' . $lead . '</span>/<span title="Целевые">' . $target . '</span>';
            }
        ],
        [
            'attribute' => 'ak_rub',
            'label' => 'CPL',
            'value' => function ($row) use ($model, $stat) {
                $lead = 0;

                if (isset($stat['listingLeads'][$row->idd])) {
                    $lead = $stat['listingLeads'][$row->idd]['lead'];
                }

                if (isset($model->cianLists[$row->id]) && isset($stat['listingLeads'][$model->cianLists[$row->id]['list']->id])) {
                    $lead += $stat['listingLeads'][$model->cianLists[$row->id]['list']->id]['lead'];
                }

                if ($lead > 0) {
                    return number_format($stat['budget'] / $lead, 0, '.', ' ');
                }

                return 0;
            }
        ],
        [
            'class' => 'yii\grid\ActionColumn',
            'header' => Html::button('Сохранить ставки', ['id' => 'save_bets', 'class' => 'btn btn-primary', 'disabled' => true]),
            'headerOptions' => ['class' => 'text-center'],
            'template' => (Yii::$app->user->can('broker') ? '' : '{on_board} ') . '{exclusive} {on_site} {video} {layouts} {photos} {update} {deleteBasket}',
            'buttons' => [
                'update' => function ($url, $model) {
                    return Html::a('<span class="glyphicon glyphicon-pencil"></span>', ['listing/update', 'id' => $model->id], [
                        'title' => Yii::t('yii', 'Update'),
                        'aria-label' => Yii::t('yii', 'Update'),
                        'data-pjax' => 0,
                    ]);
                },
                'deleteBasket' => function ($url, $model) use ($chelyad) {
                    if ($chelyad && $model->object->user_id != Yii::$app->user->id) {
                        return false;
                    }
                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', '#', [
                        'class' => 'basket-actions',
                        'data' => [
                            'table' => $model->tableName(),
                            'id-basket' => $model->id,
                            'pjax' => 0,
                            'link' => '/basket/add-to-basket',
                            'message' => 'Удален в корзину!',
                        ],
                    ]);
                },
                'on_board' => function ($url, $row, $id) use ($model, $ads, $listingsCian) {
                    $boards = '';

                    if (in_array($row->id, array_keys($model->cianLists))) {
                        $link = $model->cianLists[$row->id]['list']->link;
                        if (!$link) {
                            $link = $model->getCianLink($model->cianLists[$row->id]['list']->id, 'list');
                        }

                        $boards .= $link ? Html::a(strtoupper(mb_substr(Ad::ALL[Ad::CIAN], 0, 1)), $link, ['class' => 'badge bg-green', 'target' => '_blank']) : Html::tag('span', strtoupper(mb_substr(Ad::ALL[Ad::CIAN], 0, 1)), ['class' => 'badge bg-green', 'title' => 'Нет ссылки на объявление']);
                        $boards .= Html::tag('span', strtoupper(mb_substr(Ad::ALL[Ad::AVITO], 0, 1)), ['class' => 'badge']);
                        $boards .= Html::tag('span', strtoupper(mb_substr(Ad::ALL[Ad::YANDEX], 0, 1)), ['class' => 'badge']);
                    } else {
                        foreach ([Ad::CIAN, Ad::AVITO, Ad::YANDEX] as $ad) {
                            $color = '';
                            $title = '';

                            if (key_exists($id, $ads)) {
                                $promos = [];
                                foreach (ListingAd::$promoOptions[$ad] as $promoKey => $v) {
                                    if ((@$ads[$id][$ad] & $promoKey) || (@$ads[$id][$ad] == 0 && $promoKey == 0)) {
                                        $promos[] = $v;
                                        $color = ' bg-green';
                                    }
                                }
                                $title = implode(', ', $promos);

                                if ($ad===Ad::CIAN && $title && $row->bet) {
                                    $title .= ' ' . $row->bet;
                                }
                            }

                            if ($ad === Ad::CIAN) {
                                $link = '';
                                if (isset($listingsCian[$row->id])) {
                                    $link = $listingsCian[$row->id]->link;
                                }
                                if (!$link) {
                                    $link = $model->getCianLink($row->idd, 'listing');
                                }

                                $boards .= $link ? Html::a(strtoupper(mb_substr(Ad::ALL[$ad], 0, 1)), $link, ['class' => 'badge'.$color, 'title' => $title, 'target' => '_blank']) : Html::tag('span', strtoupper(mb_substr(Ad::ALL[$ad], 0, 1)), ['class' => 'badge'.$color, 'title' => 'Нет ссылки на объявление']);
                            } else {
                                $boards .= Html::tag('span', strtoupper(mb_substr(Ad::ALL[$ad], 0, 1)), ['class' => 'badge'.$color, 'title' => $title]);
                            }
                        }
                    }

                    return $boards;
                },
                'exclusive' => function ($url, $row) use ($model) {
                    if (!in_array($row->id, array_keys($model->cianLists))) {
                        $data = [
                            'class' => $row->exclusive_listing ? 'fa-star' : 'fa-star-o',
                            'title' => $row->exclusive_listing ? 'Эксклюзивный' : 'Не эксклюзивный'
                        ];
                    } else {
                        $data = [
                            'class' => $model->cianLists[$row->id]['list']->hasExclusive ? 'fa-star' : 'fa-star-o',
                            'title' => $model->cianLists[$row->id]['list']->hasExclusive ? 'Эксклюзивный' : 'Не эксклюзивный'
                        ];
                    }

                    return Html::tag('span', '<i class="fa ' . $data['class'] . '"></i>', ['class' => 'text-danger', 'title' => $data['title']]);
                },
                'on_site' => function ($url, $model, $id) use ($sites) {
                    if (in_array($id, $sites) and $model->no_site == 0) {
                        return Html::a('<i class="glyphicon glyphicon-globe"></i>', 'https://www.mcity.ru/' . $model->idd, ['target' => '_blank']);
                    } else {
                        return Html::tag('span', '<i class="glyphicon glyphicon-globe text-danger"></i>');
                    }
                },
                'layouts' => function ($url, $row) use ($model) {
                    if (!in_array($row->id, array_keys($model->cianLists)) && $row->hasPlans) {
                        return Html::a('<img src="//inv.mcity.ru/img/plan.png" width="16" />', '#', ['data-toggle' => 'layouts-lightbox', 'data-id' => $row->id, 'title' => 'Планировки']);
                    }

                    return '<img src="//inv.mcity.ru/img/plan.png" width="16" title="Планировок нет" style="cursor:no-drop" />';
                },
                'photos' => function ($url, $row) use ($model) {
                    if (!in_array($row->id, array_keys($model->cianLists)) && $row->hasPhotosAndViews) {
                        return Html::a('<img src="//inv.mcity.ru/img/photo.png" width="18" />', '#', ['data-toggle' => 'trigger-lightbox', 'data-id' => $row->id, 'title' => 'Фото и Виды']);
                    } elseif ($model->cianLists[$row->id]['list']->hasPhotos) {
                        return Html::a('<img src="//inv.mcity.ru/img/photo.png" width="18" />', '#', ['data-toggle' => 'cian-list-lightbox', 'data-id' => $model->cianLists[$row->id]['list']->id, 'title' => 'Фото']);
                    }

                    return '<img src="//inv.mcity.ru/img/photo.png" width="18" title="Фото и Видов нет" style="cursor:no-drop" />';
                },
                'video' => function ($url, $row) use ($model, $userRole) {
                    if ($userRole !== 'admin') {
                        return '';
                    }

                    if (!in_array($row->id, array_keys($model->cianLists)) && !empty($row->youtube)) {
                        return '<a href="' . $row->youtube . '" target="_brank"><i class="glyphicon glyphicon-facetime-video"></i></a>';
                    } elseif (!empty($model->cianLists[$row->id]['list']->video)) {
                        return '<a href="' . $model->cianLists[$row->id]['list']->video . '" target="_brank"><i class="glyphicon glyphicon-facetime-video"></i></a>';
                    }

                    return '<span><i class="glyphicon glyphicon-facetime-video text-danger"></i></span>';
                },
            ],
        ],
    ],
]); ?>
<?php Pjax::end(); ?>
