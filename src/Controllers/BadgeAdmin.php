<?php

namespace Koseu\Controllers;

use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;

use \Tsugi\Core\LTIX;
use \Tsugi\Grades\GradeUtil;

class BadgeAdmin {

    const ROUTE = '/badgeadmin';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'BadgeAdmin@get');
        $app->router->get($prefix.'/', 'BadgeAdmin@get');
    }

    public function get(Request $request)
    {
        global $CFG, $OUTPUT;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

        // Load the Lesson
        $l = \Tsugi\UI\LessonsOrchestrator::getLessons(); // TODO

        // Load all the Grades so far into arrays mapped by user
        $gradeMap = array();
        if ( isset( $_SESSION['role']) && $_SESSION['role'] >= LTIX::ROLE_INSTRUCTOR ) {
            $rows = GradeUtil::loadGradesForAdmin();
            foreach($rows as $row) {
                if (!array_key_exists($row["user_id"], $gradeMap)) {
                    $gradeMap[$row["user_id"]] = array();
                }
                $allgrades = $gradeMap[$row["user_id"]];
                $allgrades[$row['link_key']] = $row['grade'];
                $gradeMap[$row["user_id"]] = $allgrades;
            }
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        if (file_exists($CFG->dirroot.'/../nav.php')) {
            include $CFG->dirroot.'/../nav.php';
        }
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        $l->renderBadgeAdmin($gradeMap, false);
        $OUTPUT->footer();

    }
}