<?php

namespace Koseu\Controllers;

use Tsugi\Util\U;
use Tsugi\Util\LTI;
use Tsugi\Core\LTIX;
use Tsugi\Lumen\Application;
use Illuminate\Http\Request;
use Tsugi\UI\LessonsOrchestrator;


class Administration
{

    const ROUTE = '/administration';


    public static function routes(Application $app, $prefix = self::ROUTE)
    {
        $app->router->get($prefix . '/launch', function (Request $request) use ($app) {
            global $CFG;
            $redirectUrl = $request->query('redirect_url', "{$CFG->apphome}/");
            return Administration::launch($app, $redirectUrl);
        });

        $app->router->get($prefix . '/launch/', function (Request $request) use ($app) {
            global $CFG;
            $redirectUrl = $request->query('redirect_url', "{$CFG->apphome}/");
            return Administration::launch($app, $redirectUrl);
        });

        $app->router->get($prefix, 'Administration@get');
        $app->router->get($prefix . '/', 'Administration@get');

    }

    public static function get(Request $request)
    {
        global $CFG, $OUTPUT;

        if (!isset($CFG->lessons)) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

        // Turning on and off styling
        if (isset($_GET['nostyle'])) {
            if ($_GET['nostyle'] == 'yes') {
                $_SESSION['nostyle'] = 'yes';
            } else {
                unset($_SESSION['nostyle']);
            }
        }

        // Set login redirect
        $path = U::rest_path();
        if (
            U::get($_SESSION, 'secret')
            && U::get($_SESSION, 'user_key')
            && U::get($_SESSION, 'displayname')
            && U::get($_SESSION, 'email')
        ) {
            if (!self::isAdmin()) {
                return redirect("/", 302);
            }
        } else {
            // Missing session data so redirect to login
            $app->tsugiFlashError(__('You must log in to access that tool or resource.'));
            $_SESSION["login_return"] = $path->full;
            return redirect('login');
        }
        $_SESSION["login_return"] = $path->full;

        // Load the Lesson

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        if (file_exists($CFG->dirroot . '/../nav.php')) {
            include $CFG->dirroot . '/../nav.php';
        }
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        echo ('<div class=""> Nothing to see here yet ');
        echo ('</div>');
        $OUTPUT->footerStart();
        $OUTPUT->footerEnd();
    }

    public static function launch(Application $app, $redirectUrl)
    {
        global $CFG;
        // $tsugi = $app['tsugi'];

        $path = U::rest_path();
        $redirect_path = U::addSession($path->parent);
        if ($redirect_path == '') $redirect_path = '/';

        // Check that the session has the minimums...
        if (
            self::isAdmin()
            && U::get($_SESSION, 'secret')
            && U::get($_SESSION, 'user_key')
            && U::get($_SESSION, 'displayname')
            && U::get($_SESSION, 'email')
        ) {
            // All good
        } else {
            // Missing session data so redirect to login
            $app->tsugiFlashError(__('You must log in to access that tool or resource.'));
            $_SESSION["login_return"] = $path->full;
            return redirect('login');
        }

        $key = isset($_SESSION['oauth_consumer_key']) ? $_SESSION['oauth_consumer_key'] : false;
        $secret = false;
        if (isset($_SESSION['secret'])) {
            $secret = LTIX::decrypt_secret($_SESSION['secret']);
        }
        $resource_link_id = "learn-admin";
        $resource_link_title = "LEARN Admin";
        $parms = array(
            'lti_message_type' => 'basic-lti-launch-request',
            'resource_link_id' => $resource_link_id,
            'resource_link_title' => $resource_link_title,
            'tool_consumer_info_product_family_code' => 'tsugi',
            'tool_consumer_info_version' => '1.1',
            'context_id' => md5("learn_admin"),
            'context_label' => "LEARN Admin",
            'context_title' => "LEARN Admin",
            'user_id' => $_SESSION['user_key'],
            'lis_person_name_full' => $_SESSION['displayname'],
            'lis_person_contact_email_primary' => $_SESSION['email'],
            'roles' => isset($_SESSION["admin"]) ? 'Instructor' : 'Learner',
        );
        if (isset($_SESSION['avatar'])) $parms['user_image'] = $_SESSION['avatar'];

        if (isset($pageAnchor)) {
            $redirectUrl .= "/{$pageAnchor}";
        }

        $parms['launch_presentation_return_url'] = $redirectUrl;

        $sess_key = 'tsugi_top_nav_' . $CFG->wwwroot;
        if (isset($_SESSION[$sess_key])) {
            // $parms['ext_tsugi_top_nav'] = $_SESSION[$sess_key];
        }

        $form_id = "tsugi_form_id_" . bin2Hex(openssl_random_pseudo_bytes(4));
        $parms['ext_lti_form_id'] = $form_id;

        $endpoint = "/mod/learn-registration/?view=ADMINISTRATION";
        U::absolute_url_ref($endpoint);


        if (isset($redirectUrl) && strlen($redirectUrl) > 0) {
            $parms['redirect_url'] = $redirectUrl;
        }


        $parms = LTI::signParameters(
            $parms,
            $endpoint,
            "POST",
            $key,
            $secret,
            "Finish Launch",
            $CFG->wwwroot,
            $CFG->servicename
        );

        $content = LTI::postLaunchHTML($parms, $endpoint, false /*debug */);
        print($content);
        return "";
    }

    protected static function isAdmin() {
        return (isset($_SESSION['role']) && $_SESSION['role'] >= LTIX::ROLE_ADMINISTRATOR ) ||
            (isset( $_SESSION['admin']) && $_SESSION['admin'] == 'yes');
    }
}
