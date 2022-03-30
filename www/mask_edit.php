<?php
    require_once  __DIR__ . '/layouts/header.php';

    $pagesGroup = ['settings', 'menu'];

    use OsT\Access;
    use OsT\Base\Form;
    use OsT\Base\Message;
    use OsT\Base\System;
    use OsT\Serviceload\Mask;
    use OsT\Serviceload\Place;
    use OsT\Serviceload\Schedule;
    use OsT\Serviceload\Type;

    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        if ($id) {
            $mask = new Mask($id);
            if ($mask->workability) {
                if (Access::checkAccess('mask_edit', ['user' => $mask->user])) {
                    $mode = MODE_EDIT;
                    $pageData['title'] = 'Редактирование шаблона';

                } else {
                    new Message('Вам отказано в редактировании данных этого шаблона', Message::TYPE_ACCESS);
                    System::location('index.php');
                    exit;
                }
            } else {
                new Message('Шаблона, данные которого вы пытаетесь изменить не существует', Message::TYPE_WARNING);
                System::location('index.php');
                exit;
            }

        } else {
            new Message('Шаблона, данные которого вы пытаетесь изменить не существует', Message::TYPE_WARNING);
            System::location('index.php');
            exit;
        }

    } else {
        if (Access::checkAccess('masks_show')) {
            $mode = MODE_INSERT;
            $pageData['title'] = 'Добавление шаблона';

        } else {
            new Message('Вам отказано в добавлении нового шаблона', Message::TYPE_ACCESS);
            System::location('index.php');
            exit;
        }
    }

    if (isset($_POST['submit'])) {
        $data ['title'] = addslashes(trim ($_POST [ 'title' ]));
        $data ['data'][ 'type' ] = trim($_POST [ 'type' ]);
        $data ['data'][ 'incoming' ] = intval ($_POST [ 'incoming' ]);
        $data ['data'][ 'from' ] = intval ($_POST [ 'from' ]);
        $data ['data'][ 'len' ] = intval ($_POST [ 'len' ]);
        $data ['data'][ 'place' ] = trim($_POST [ 'place' ]);

        if ( $data [ 'title' ] !== '' && $data['data']['len']  && $data ['data'][ 'type' ] !== '' && $data ['data'][ 'place' ] !== '') {
            $data['data']['place'] = Place::getIdByTitle($data['data']['place'], true);
            $data['data']['type'] = Type::getSubtypeIdByTitle($data['data']['type'], true);
            $data['data']['rage'] = [];
            foreach ($_POST as $key => $value)
                if (strpos($key, 'm_') === 0)
                    $data['data']['rage'] [] = intval(substr($key, 2));

            $data['data'] = json_encode($data['data']);

            if ($mode === MODE_INSERT) {
                $data [ 'user' ] = $USER->id;
                $data [ 'enabled' ] = 1;

                if (Mask::insert($data)) {
                    System::location('mask.php');
                    new Message('Шаблон успешно добавлен', Message::TYPE_OK);
                    exit;

                } else {
                    new Message('В процессе добавления данных произошла ошибка', Message::TYPE_ERROR);
                    System::location($_SERVER['REQUEST_URI']);
                    exit;
                }

            } elseif ($mode === MODE_EDIT) {
                if ($mask->update($data)) {
                    new Message('Данные шаблона изменены', Message::TYPE_OK);
                    System::location('mask.php');
                    exit;

                } else {
                    new Message('В процессе обновления данных произошла ошибка', Message::TYPE_ERROR);
                    System::location($_SERVER['REQUEST_URI']);
                    exit;
                }
            }

        } else {
            new Message('Были заполнены не все поля', Message::TYPE_ERROR);
            System::location($_SERVER['REQUEST_URI']);
            exit;
        }

    }

    require_once  __DIR__ . '/layouts/head.php';
?>

    <link rel="stylesheet" href="css/form.css">
    <script src="js/form.js"></script>
    <script src="js/textSelect.js"></script>

    <script>
        function structOpenList(ell) {
            $(ell).siblings('.structItemBody').slideToggle();
        }

        function rageCheck(ell) {
            // Установить текущее значение checkbox всем дочерним
            $(ell).siblings('.structItemBody').find('input').each(function () {
                if ($(ell).is(':checked'))
                    this.checked = true;
                else this.checked = false;
            });
            rageCheckParents(ell);
        }

        function rageCheckParents(ell) {
            // Изменить значение родительских checkbox
            body = $(ell).parent().parent();
            is_not_checked = false;
            while ($(body).hasClass('structItemBody')) {
                is_checked = false;
                $(body).children().each(function () {
                    if ($(this).children('input').is(':checked'))
                        is_checked = true;
                    else is_not_checked = true;
                });
                $(body).siblings('input')[0].checked = is_checked;
                if (is_checked && is_not_checked)
                    $(body).css('display', 'block');
                body = $(body).parent().parent();
            }
        }

        $(document).ready(function () {
           $('#rage').find('.rage_m').each(function () {
               rageCheckParents(this);
           });
        });
    </script>

    <style>
        .bodyBox {
            width: 80%;
            margin: 20px auto;
            display: inline-block;
            position: relative;
        }
        .pageTitleLine {
            font-family: RalewayB, sans-serif;
            font-size: 24px;
            text-align: left;
            color: #6f6f6f;
            padding: 0 0 15px 0;
            cursor: default;
        }

        .tableBodyBox {
            background-color: #ffffffe8;
            padding: 20px;
            display: inline-block;
            position: relative;
            width: 100%;
        }
        .tableButtonsLine {
            padding: 0 0 10px 0;
            display: inline-block;
            width: 100%;
        }
        .tableButtonsLine .slideButton.new {
            margin: 0;
            float: right;
            text-decoration: none;
        }


        #rage .itemBody {
            padding-bottom: 5px;
        }
        .structItemBox {
            width: 100%;
            position: relative;
            text-align: left;
            display: inline-block;
        }
        .structItemBox input {
            width: 20px;
            height: 20px;
            margin: 5px 7px 0 5px;
            float: left;
        }
        .structItemBox span {
            padding: 6px 0 0;
            float: left;
            cursor: pointer;
        }
        .structItemBody {
            display: none;
            border-left: 2px solid #ccc;
            width: calc(100% - 15px);
            float: right;
            position: relative;
            box-sizing: border-box;
            padding: 3px 0 0 9px;
        }
    </style>

    <div class="bodyBox no_select">

        <div class="pageTitleLine">
            <?php echo $pageData['title'];?>
        </div>

        <form class="form" action="<?php echo $_SERVER [ 'REQUEST_URI' ] ?>" method="post" enctype="multipart/form-data">

            <div class="tableBodyBox">
                <div class="tableButtonsLine">
                    <div class="slideButton new" onclick="$('.formSubmit').click()">Добавить</div>
                    <input class="formSubmit hidden" name="submit" type="submit" value="">
                </div>


            <?php

            if ($mode === MODE_EDIT) {
                $value = $mask->title;
            } else $value = '';
            echo Form::getItemText(
                'Название',
                [
                    'name' => 'title',
                    'placeholder' => 'Наряд БП-001, с9, 12ч',
                    'maxlength' => '50',
                    'value' => $value
                ],
                'Поле является обязательным для заполнения. Русские символы. ');

            $subtypes = Type::getData([Type::NARYAD], ['sub_types']);
            $subtypes = $subtypes[Type::NARYAD]['sub_types'];
            $servicetype_value = '';
            if (count($subtypes)) {
                foreach ($subtypes as $key => $title) {
                    if ($mode === MODE_EDIT)
                        if ($key === $mask->data['type'])
                            $servicetype_value = $title;
                }
            }
            echo Form::getItemTextSelect(
                'Тип службы',
                [
                    'name' => 'type',
                    'placeholder' => 'Наряд',
                    'maxlength' => '20',
                    'value' => $servicetype_value,
                    'haystack' => $subtypes
                ],
                'Поле является обязательным для заполнения. Русские символы. ');

            $data = [];
            for ($i=0; $i < 24; $i++)
                $data[] = ['id' => $i, 'title' => System::i2d($i) . ':00'];

            if ($mode === MODE_EDIT) {
                $value = $mask->data['incoming'];
            } else $value = $SETTINGS['TIME_RABOCHIY_START'];
            echo Form::getItemSelect(
                'Время прибытия',
                'incoming',
                $data,
                $value
            );

            if ($mode === MODE_EDIT) {
                $value = $mask->data['from'];
            } else $value = $SETTINGS['TIME_RABOCHIY_START'];
            echo Form::getItemSelect(
                'Время заступления',
                'from',
                $data,
                $value
            );

            $data = [];
            $length = Schedule::getNaryadLengthArr();
            foreach ($length as $value)
                $data[] = ['id' => $value, 'title' => $value . ' ч.'];
            if ($mode === MODE_EDIT) {
                $value = $mask->data['len'];
            } else $value = 0;
            echo Form::getItemSelect(
                'Продолжительность',
                'len',
                $data,
                $value
            );

            $places = Place::getData(null , [
                'id',
                'title'
            ]);
            $place_value = '';
            if (count($places)) {
                foreach ($places as $key=>$item) {
                    $places[$key] = $item['title'];
                    if ($mode === MODE_EDIT)
                        if ($item['id'] === $mask->data['place'])
                            $place_value = $item['title'];
                }
            }
            echo Form::getItemTextSelect(
                'Место',
                [
                    'name' => 'place',
                    'placeholder' => 'БП-001',
                    'maxlength' => '30',
                    'value' => $place_value,
                    'haystack' => $places
                ],
                'Поле является обязательным для заполнения. Русские символы. ');

            $rage = [];
            if ($mode === MODE_EDIT)
                $rage = $mask->data['rage'];
            $content = Mask::genHtmlRageList($STRUCT_TREE, $rage);
            echo Form::getItemBlock(
                'Область применения',
                'rage',
                $content,
                null,
                [
                    'opened' => true
                ]
            );
            ?>
            </div>
        </form>

    </div>

<?php
    require_once __DIR__ . '/layouts/footer.php';
?>
