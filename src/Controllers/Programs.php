<?php

namespace Koseu\Controllers;

use CourseBase;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;

use \Tsugi\Grades\GradeUtil;
use Tsugi\UI\LessonsUIHelper;
use Tsugi\Util\U;

class Programs
{

    const ROUTE = '/programs';

    public static function routes(Application $app, $prefix = self::ROUTE)
    {
        $app->router->get($prefix, 'Programs@get');
        $app->router->get($prefix . '/', 'Programs@get');
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
        LessonsUIHelper::renderGeneralHeader();
        $OUTPUT->bodyStart();
        if (file_exists($CFG->dirroot . '/../nav.php')) {
            include $CFG->dirroot . '/../nav.php';
        }
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();

        // Get all courses
        $allCourses = [];
        foreach ($lessonsList as $l) {
            $pageData = $l->getAllProgramsPageData();
            $allCourses[] = $pageData;
        }
        // Get courses that have been started

        // Get courses that are complete

        // Render programs page
        $twig = LessonsUIHelper::twig();
        echo $twig->render('programs.twig', [
            'allCourses' => $allCourses,
            'allProgramsImage' => $CFG->wwwroot . '/vendor/tsugi/lib/src/UI/assets/UD-COL-110.jpg',
            'allProgramsImageAlt' => 'University of Dayton Chapel'
        ]);

        $OUTPUT->footer();
    }
}
