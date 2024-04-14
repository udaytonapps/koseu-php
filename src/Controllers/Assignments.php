<?php

namespace Koseu\Controllers;

use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;

use \Tsugi\Grades\GradeUtil;
use Tsugi\UI\LessonsOrchestrator;
use Tsugi\UI\LessonsUIHelper;
use Tsugi\Util\U;

class Assignments
{

    const ROUTE = '/progress';

    public static function routes(Application $app, $prefix = self::ROUTE)
    {
        $app->router->get($prefix, 'Assignments@get');
        $app->router->get($prefix . '/', 'Assignments@get');
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
        $moduleData = [];
        foreach ($referenceList as $key => $reference) {
            $l = \Tsugi\UI\LessonsOrchestrator::getLessons($key);
            array_push($lessonsList, $l);
            $response = $l->getProgressData($key, $l);
            $moduleData[] = $response;
        }

        // Load all the Grades so far
        $allgrades = array();
        $alldates = array();
        if (isset($_SESSION['id']) && isset($_SESSION['context_id'])) {
            $rows = GradeUtil::loadGradesForCourse($_SESSION['id'], $_SESSION['context_id']);
            foreach ($rows as $row) {
                $allgrades[$row['resource_link_id']] = $row['grade'];
                $alldates[$row['resource_link_id']] = $row['updated_at'];
            }
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        if (file_exists($CFG->dirroot . '/../nav.php')) {
            include $CFG->dirroot . '/../nav.php';
        }
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        $l->renderAssignments($allgrades, $alldates, false);

        $twig = LessonsUIHelper::twig();

        $twig->addFunction(new \Twig\TwigFunction('getModuleByRlid', function ($course, $resource_link_id) {
            return LessonsOrchestrator::getModuleByRlid($course, $resource_link_id);
        }));
        $twig->addFunction(new \Twig\TwigFunction('getLtiByRlid', function ($course, $resource_link_id) {
            return LessonsOrchestrator::getLtiByRlid($course, $resource_link_id);
        }));
        $twig->addFunction(new \Twig\TwigFunction('getGradeByRlid', function ($grades, $resource_link_id) {
            if (isset($grades) && isset($grades[$resource_link_id])) {
                return $grades[$resource_link_id];
            }
        }));

        echo $twig->render('progress.twig', [
            'modules' => $moduleData,
            'loggedIn' => isset($_SESSION['id'])
        ]);

        $OUTPUT->footer();
    }
}
