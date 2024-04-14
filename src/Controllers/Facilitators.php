<?php


namespace Koseu\Controllers;

use Tsugi\Util\U;
use Tsugi\Util\LTI;
use Tsugi\Core\LTIX;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;

class Facilitators
{

    const ROUTE = '/facilitators';

    const REDIRECT = 'koseu_controllers_facilitators';

    public static function routes(Application $app, $prefix = self::ROUTE)
    {
        $app->router->get($prefix . '/{facilitatorId}', function (Request $request, $facilitatorId) use ($app) {
            return Facilitators::getSingle($facilitatorId);
        });
        $app->router->get($prefix, 'Facilitators@get');
        $app->router->get($prefix . '/', 'Facilitators@get');
        $app->router->get('/' . self::REDIRECT, 'Facilitators@get');
    }

    public static function getSingle($facilitatorId = null)
    {
        global $CFG, $OUTPUT;

        if (!isset($CFG->lessons)) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

        // Set login redirect
        $path = U::rest_path();
        $_SESSION["login_return"] = $path->full;

        // Load the Lesson
        $l = new \Tsugi\UI\Facilitators($CFG->lessons);

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        if (file_exists($CFG->dirroot.'/../nav.php')) {
            include $CFG->dirroot.'/../nav.php';
        }
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        $l->header();
        echo('<div class="container">');
        $l->render(false, $facilitatorId);
        echo('</div>');
        $OUTPUT->footerStart();
        $l->footer();
        $OUTPUT->footerEnd();
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

        // Load the Lesson
        $l = new \Tsugi\UI\Facilitators($CFG->lessons);

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        if (file_exists($CFG->dirroot.'/../nav.php')) {
            include $CFG->dirroot.'/../nav.php';
        }
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        $l->header();
        echo('<div class="container">');
        $l->render();
        echo('</div>');
        $OUTPUT->footerStart();
        $l->footer();
        $OUTPUT->footerEnd();
    }
}
