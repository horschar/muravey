<?php
/**
 * Служит API осносткой системы
 * Выполнение глобальных команд путем GET запросов к данный странице
 * @example http://rosgvard.local/api.php?key=delete_serviseload_mask&id=1
 *
 * Ключи key
 *
 * delete_serviseload_mask      Удалить шаблоны службы
 *      id                          идентификатор шаблона (удалить шаблон по id)
 *      user                        идентификатор пользователя (удалить все шаблоны пользователя user)
 *      all                         удалить все шаблоны
 * delete_serviseload_place     Удалить места несения службы
 *      id                          идентификатор места (удалить по id)
 *      title                       наименование места (удалить по title)
 *      unused                      удалить те, которые нигде не используются
 *      all                         удалить все места
 * delete_serviseload_type      Удалить тип служебной нагрузки
 *      id                          идентификатор типа (удалить по id)
 *      all                         удалить все типы
 * delete_serviseload_subtype   Удалить подтипы службы
 *      id                          идентификатор подтипа (удалить по id)
 *      title                       наименование подтипа (удалить по title)
 *      unused                      удалить те, которые нигде не используются
 *      all                         удалить все подтипы
 * delete_serviseload           Удалить служебную нагрузку
 *      military                    идентификатор военнослужащего (удалить по military)
 *      all                         удалить все записи
 * clear_serviseload            Очистить служебную нагрузку
 *      military                    идентификатор военнослужащего (очистить по military)
 *      all                         очистить нагрузку всех военнослужащих
 * delete_package               Удалить пакет отчетов
 *      id                          идентификатор пакета отчетов (удалить по id)
 *      user                        идентификатор пользователя (удалить все пакеты отчетов пользователя user)
 *      all                         удалить все пакеты отчетов
 * delete_user                  Удалить пользователя
 *      id                          идентификатор пользователя (удалить по id)
 *      all                         удалить всех пользователей
 * clear_user_settings          Очистить персональные настройки пользователя
 *      id                          идентификатор пользователя (удалить по id)
 *      all                         всех пользователей
 * delete_military_level        Удалить звание военнослужащего
 *      id                          идентификатор присвоенного звания (удалить по id)
 *      military                    идентификатор военнослужащего (удалить все звания военнослужащего)
 *      all                         удалить все записи о присвоении званий
 * delete_military_absent       Удалить период отсутствия военнослужащего
 *      id                          идентификатор записи периода (удалить по id)
 *          dependencies                откорректировать график служебной нагрузки
 *      military                    идентификатор военнослужащего (удалить все периоды отсутствия военнослужащего)
 *          dependencies                откорректировать график служебной нагрузки
 *      all                         удалить все записи о периодах отсутствия
 *          dependencies                откорректировать график служебной нагрузки
 * delete_military_state        Удалить должность военнослужащего
 *      id                          идентификатор записи должности (удалить по id)
 *          dependencies                удалить все зависимые данные
 *      military                    идентификатор военнослужащего (удалить все должности военнослужащего)
 *          dependencies                удалить все зависимые данные
 *      state                       идентификатор должности state (unit_state->id) (удалить все записи по данной должности)
 *          dependencies                удалить все зависимые данные
 *      all                         удалить все записи о должностях военнослужащих
 *          dependencies                удалить все зависимые данные
 * delete_military              Удалить военнослужащего
 *      id                          идентификатор военнослужащего (удалить по id)
 *          dependencies                удалить все зависимые данные
 *      all                         удалить всех
 *          dependencies                удалить все зависимые данные
 * delete_state                 Удалить должность
 *      id                          идентификатор должности (удалить по id)
 *          dependencies                удалить все зависимые данные
 *      all                         удалить все
 *          dependencies                удалить все зависимые данные
 * delete_unit                  Удалить подразделение
 *      id                          идентификатор подразделения (удалить по id)
 *          dependencies                удалить все зависимые данные
 *      all                         удалить все
 *          dependencies                удалить все зависимые данные
 * delete_all                   Удалить все данные системы (за исключением критически важных для запуска, типа level, settings)
 *      users                       не удалять пользователей
 *      subtypes                    не удалять подтипы службы
 *
 */

require_once  'layouts/header.php';

if (isset($_GET['key'])) {
    switch ($_GET['key']) {
        /*
         * Удалить шаблоны службы
         *      id                  идентификатор шаблона (удалить шаблон по id)
         *      user                идентификатор пользователя (удалить все шаблоны пользователя user)
         *      all                 удалить все шаблоны
         */
        case 'delete_serviseload_mask':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                \OsT\Serviceload\Mask::_delete($id);

            } elseif (isset($_GET['user'])) {
                $user = intval($_GET['user']);
                \OsT\Serviceload\Mask::_deleteByUser($user);

            } elseif (isset($_GET['all'])) {
                \OsT\Serviceload\Mask::_deleteAll();
            }
            break;

        /*
         * Удалить места несения службы
         *      id                  идентификатор места (удалить по id)
         *      title               наименование места (удалить по title)
         *      unused              удалить те, которые нигде не используются
         *      all                 удалить все места
         */
        case 'delete_serviseload_place':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                \OsT\Serviceload\Place::_delete($id);

            } elseif (isset($_GET['title'])) {
                $title = $_GET['title'];
                \OsT\Serviceload\Place::_deleteByTitle($title);

            } elseif (isset($_GET['unused'])) {
                \OsT\Serviceload\Place::_deleteUnused();

            } elseif (isset($_GET['all'])) {
                \OsT\Serviceload\Place::_deleteAll();
            }
            break;

        /*
         * Удалить тип служебной нагрузки
         *      id                  идентификатор типа (удалить по id)
         *      all                 удалить все типы
         */
        case 'delete_serviseload_type':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                \OsT\Serviceload\Type::_delete($id);

            } elseif (isset($_GET['all'])) {
                \OsT\Serviceload\Type::_deleteAll();
            }
            break;

        /*
         * Удалить подтипы службы
         *      id                  идентификатор подтипа (удалить по id)
         *      title               наименование подтипа (удалить по title)
         *      unused              удалить те, которые нигде не используются
         *      all                 удалить все подтипы
         */
        case 'delete_serviseload_subtype':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                \OsT\Serviceload\Type::_deleteSubtype($id);

            } elseif (isset($_GET['title'])) {
                $title = $_GET['title'];
                \OsT\Serviceload\Type::_deleteSubtypeByTitle($title);

            } elseif (isset($_GET['unused'])) {
                \OsT\Serviceload\Type::_deleteUnusedSubtype();

            } elseif (isset($_GET['all'])) {
                \OsT\Serviceload\Type::_deleteSubtypeAll();
            }
            break;

        /*
         * Удалить служебную нагрузку
         *      military            идентификатор военнослужащего (удалить по military)
         *      all                 удалить все записи
         */
        case 'delete_serviseload':
            if (isset($_GET['military'])) {
                $military = intval($_GET['military']);
                \OsT\Serviceload\Military::_delete($military);

            } elseif (isset($_GET['all'])) {
                \OsT\Serviceload\Military::_deleteAll();
            }
            break;

        /*
         * Очистить служебную нагрузку
         *      military            идентификатор военнослужащего (очистить по military)
         *      all                 очистить нагрузку всех военнослужащих
         */
        case 'clear_serviseload':
            if (isset($_GET['military'])) {
                $military = intval($_GET['military']);
                \OsT\Serviceload\Military::_clear($military);

            } elseif (isset($_GET['all'])) {
                \OsT\Serviceload\Military::_clearAll();
            }
            break;

        /*
         * Удалить пакет отчетов
         *      id                  идентификатор пакета отчетов (удалить по id)
         *      user                идентификатор пользователя (удалить все пакеты отчетов пользователя user)
         *      all                 удалить все пакеты отчетов
         */
        case 'delete_package':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                \OsT\Reports\Package::_delete($id);

            } elseif (isset($_GET['user'])) {
                $user = intval($_GET['user']);
                \OsT\Reports\Package::_deleteByUser($user);

            } elseif (isset($_GET['all'])) {
                \OsT\Reports\Package::_deleteAll();
            }
            break;

        /*
         * Удалить пользователя
         *      id                  идентификатор пользователя (удалить по id)
         *      all                 удалить всех пользователей
         */
        case 'delete_user':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                \OsT\User::_delete($id);

            } elseif (isset($_GET['all'])) {
                \OsT\User::_deleteAll();
            }
            break;

        /*
         * Очистить персональные настройки пользователя
         *      id                  идентификатор пользователя (удалить по id)
         *      all                 всех пользователей
         */
        case 'clear_user_settings':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                \OsT\User::_clearSettings($id);

            } elseif (isset($_GET['all'])) {
                \OsT\User::_clearSettingsAll();
            }
            break;

        /*
         * Удалить звание военнослужащего
         *      id                  идентификатор присвоенного звания (удалить по id)
         *      military            идентификатор военнослужащего (удалить все звания военнослужащего)
         *      all                 удалить все записи о присвоении званий
         */
        case 'delete_military_level':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                \OsT\Military\Level::_delete($id);

            } elseif (isset($_GET['military'])) {
                $military = intval($_GET['military']);
                \OsT\Military\Level::_deleteByMilitary($military);

            } elseif (isset($_GET['all'])) {
                \OsT\Military\Level::_deleteAll();
            }
            break;

        /*
         * Удалить период отсутствия военнослужащего
         *      id                  идентификатор записи периода (удалить по id)
         *          dependencies        откорректировать график служебной нагрузки
         *      military            идентификатор военнослужащего (удалить все периоды отсутствия военнослужащего)
         *          dependencies        откорректировать график служебной нагрузки
         *      all                 удалить все записи о периодах отсутствия
         *          dependencies        откорректировать график служебной нагрузки
         */
        case 'delete_military_absent':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                if (isset($_GET['dependencies'])) {
                    $absent = new \OsT\Military\Absent($id);
                    $absent->deleteWithDependencies();
                } else \OsT\Military\Absent::_delete($id);

            } elseif (isset($_GET['military'])) {
                $military = intval($_GET['military']);
                if (isset($_GET['dependencies']))
                    \OsT\Military\Absent::_deleteByMilitaryWithDependencies($military);
                else \OsT\Military\Absent::_deleteByMilitary($military);

            } elseif (isset($_GET['all'])) {
                if (isset($_GET['dependencies']))
                    \OsT\Military\Absent::_deleteAllWithDependencies();
                else \OsT\Military\Absent::_deleteAll();
            }
            break;

        /*
         * Удалить должность военнослужащего
         *      id                  идентификатор записи должности (удалить по id)
         *          dependencies        удалить все зависимые данные
         *      military            идентификатор военнослужащего (удалить все должности военнослужащего)
         *          dependencies        удалить все зависимые данные
         *      state               идентификатор должности state (unit_state->id) (удалить все записи по данной должности)
         *          dependencies        удалить все зависимые данные
         *      all                 удалить все записи о должностях военнослужащих
         *          dependencies        удалить все зависимые данные
         */
        case 'delete_military_state':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                if (isset($_GET['dependencies'])) {
                    $state = new \OsT\Military\State($id);
                    $state->deleteWithDependencies();
                } else \OsT\Military\State::_delete($id);

            } elseif (isset($_GET['military'])) {
                $military = intval($_GET['military']);
                if (isset($_GET['dependencies']))
                    \OsT\Military\State::_deleteByMilitaryWithDependencies($military);
                else \OsT\Military\State::_deleteByMilitary($military);

            } elseif (isset($_GET['state'])) {
                $state = intval($_GET['state']);
                if (isset($_GET['dependencies']))
                    \OsT\Military\State::_deleteByStateWithDependencies($state);
                else \OsT\Military\State::_deleteByState($state);

            } elseif (isset($_GET['all'])) {
                if (isset($_GET['dependencies']))
                    \OsT\Military\State::_deleteAllWithDependencies();
                else \OsT\Military\State::_deleteAll();
            }
            break;

        /*
         * Удалить военнослужащего
         *      id                  идентификатор военнослужащего (удалить по id)
         *          dependencies        удалить все зависимые данные
         *      all                 удалить всех
         *          dependencies        удалить все зависимые данные
         */
        case 'delete_military':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                if (isset($_GET['dependencies'])) {
                    \OsT\Military\Military::_deleteWithDependencies($id);
                } else \OsT\Military\Military::_delete($id);

            } elseif (isset($_GET['all'])) {
                if (isset($_GET['dependencies']))
                    \OsT\Military\Military::_deleteAllWithDependencies();
                else \OsT\Military\Military::_deleteAll();
            }
            break;

        /*
         * Удалить должность
         *      id                  идентификатор должности (удалить по id)
         *          dependencies        удалить все зависимые данные
         *      unit                идентификатор подразделения (удалить все должности в подразделении unit (и его дочерних))
         *          dependencies        удалить все зависимые данные
         *      all                 удалить все
         *          dependencies        удалить все зависимые данные
         */
        case 'delete_state':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                if (isset($_GET['dependencies'])) {
                    \OsT\State::_deleteWithDependencies($id);
                } else \OsT\State::_delete($id);

            } elseif (isset($_GET['unit'])) {
                $unit = intval($_GET['unit']);
                if (isset($_GET['dependencies']))
                    \OsT\State::_deleteByUnitWithDependencies($unit);
                else \OsT\State::_deleteByUnit($unit);

            } elseif (isset($_GET['all'])) {
                if (isset($_GET['dependencies']))
                    \OsT\State::_deleteAllWithDependencies();
                else \OsT\State::_deleteAll();
            }
            break;

        /*
         * Удалить подразделение
         *      id                  идентификатор подразделения (удалить по id)
         *          dependencies        удалить все зависимые данные
         *      all                 удалить все
         *          dependencies        удалить все зависимые данные
         */
        case 'delete_unit':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                if (isset($_GET['dependencies'])) {
                    \OsT\Unit::_deleteWithDependencies($id);
                } else \OsT\Unit::_delete($id);

            } elseif (isset($_GET['all'])) {
                if (isset($_GET['dependencies']))
                    \OsT\Unit::_deleteAllWithDependencies();
                else \OsT\Unit::_deleteAll();
            }
            break;

        /*
         * Удалить все данные системы (за исключением критически важных для запуска, типа level, settings)
         *      users               не удалять пользователей
         *      subtypes            не удалять подтипы службы
         */
        case 'delete_all':
            \OsT\Unit::_deleteAll();
            \OsT\State::_deleteAll();
            \OsT\Military\State::_deleteAll();
            \OsT\Military\Level::_deleteAll();
            \OsT\Military\Absent::_deleteAll();
            \OsT\Military\Military::_deleteAll();
            \OsT\Serviceload\Military::_deleteAll();
            \OsT\Reports\Package::_deleteAll();
            \OsT\Serviceload\Mask::_deleteAll();
            \OsT\Serviceload\Place::_deleteAll();
            if (isset($_GET['users']))
                \OsT\User::_clearSettingsAll();
            else \OsT\User::_deleteAll();
            if (!isset($_GET['subtypes']))
                \OsT\Serviceload\Type::_deleteSubtypeAll();
            break;

    }

}