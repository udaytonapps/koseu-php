<?php

namespace Koseu\Controllers;

use CourseBase;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;

use \Tsugi\Grades\GradeUtil;
use Tsugi\UI\LessonsUIHelper;
use Tsugi\Util\U;

class Browse
{

    const ROUTE = '/browse';

    public static function routes(Application $app, $prefix = self::ROUTE)
    {
        $app->router->get($prefix, 'Browse@get');
        $app->router->get($prefix . '/', 'Browse@get');
    }

    public function get(Request $request)
    {
        global $CFG, $OUTPUT;

        if (!isset($CFG->lessons)) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

        // Set login redirect
        $path = U::rest_path();
        $_SESSION["login_return"] = $path->full;

        // Load all Lessons
        $referenceList = \Tsugi\UI\LessonsOrchestrator::getLessonsReference();

        /** @var CourseBase[] */
        $lessonsList = [];

        foreach ($referenceList as $key => $reference) {
            $l = \Tsugi\UI\LessonsOrchestrator::getLessons($key);
            array_push($lessonsList, $l);
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        if (file_exists($CFG->dirroot . '/../nav.php')) {
            include $CFG->dirroot . '/../nav.php';
        }
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();

        $twig = LessonsUIHelper::twig();
        $allCourses = [];
        foreach ($lessonsList as $l) {
            $courseData = $l->getModuleData();
            array_push($allCourses, $courseData);
        }
        // Render browse page
        echo $twig->render('browse.twig', ['allCourses' => $allCourses]);

        $OUTPUT->footer();
    }
}
