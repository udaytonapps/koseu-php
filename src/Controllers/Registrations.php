<?php


namespace Koseu\Controllers;

use Tsugi\Util\U;
use Tsugi\Util\LTI;
use Tsugi\Core\LTIX;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;

class Registrations
{

    const ROUTE = '/registrations';

    const REDIRECT = 'koseu_controllers_registrations';

    public static function routes(Application $app, $prefix = self::ROUTE)
    {
        $app->router->get($prefix, 'Registrations@get');
        $app->router->get($prefix . '/', 'Registrations@get');
        $app->router->get('/' . self::REDIRECT, 'Registrations@get');
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
        $l = new \Tsugi\UI\Registrations($CFG->lessons);

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
