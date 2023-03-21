<?php

namespace Koseu\Controllers;

use Tsugi\Util\U;
use Tsugi\Util\LTI;
use Tsugi\Core\LTIX;
use Tsugi\Lumen\Application;
use Illuminate\Http\Request;
use Tsugi\UI\LessonsOrchestrator;

class Lessons
{

    const ROUTE = '/programs';

    const REDIRECT = 'koseu_controllers_lessons';

    public static function routes(Application $app, $prefix = self::ROUTE)
    {
        $app->router->get($prefix . '/{program}/{module}/lti-launch/{ltiAnchor}', function (Request $request, $program = null, $module = null, $ltiAnchor = null) use ($app) {
            $redirectUrl = $request->query('redirect_url', '/');
            $autoRegisterId = $request->query('auto_register_id');
            if (isset($autoRegisterId)) {
                $_SESSION["auto_register_id"] = $autoRegisterId;
            }
            return Lessons::launch($app, $program, $module, null, $ltiAnchor, $redirectUrl);
        });
        $app->router->get($prefix . '/{program}/{module}/{page}/lti-launch/{ltiAnchor}', function (Request $request, $program = null, $module = null, $page = null, $ltiAnchor = null) use ($app) {
            $redirectUrl = $request->query('redirect_url', '/');
            // $autoRegisterId = $request->query('auto_register_id');
            // if (isset($autoRegisterId)) {
            //     $_SESSION["auto_register_id"] = $autoRegisterId;
            // }
            return Lessons::launch($app, $program, $module, $page, $ltiAnchor, $redirectUrl);
        });
        $app->router->get($prefix . '/{program}/{module}', 'Lessons@get');
        $app->router->get($prefix . '/{program}/{module}/{page}', 'Lessons@get');
        // $app->router->get($prefix . '/{course}/{??module??}/badges', 'Lessons@get');
        // /badges .get()
        // $app->router->get($prefix . '/{course}/{??module??}/discussions', 'Lessons@get');
        // /discussions .get()
        // $app->router->get($prefix . '/{course}/{??module??}/progress', 'Lessons@get');
        // /progress .get()

        $app->router->get($prefix . '/{program}', 'Lessons@get');
        $app->router->get($prefix, 'Lessons@get');
        $app->router->get($prefix . '/', 'Lessons@get');
        $app->router->get('/' . self::REDIRECT, 'Lessons@get');
    }

    public static function get(Request $request, $anchor = null, $program = null, $module = null, $page = null)
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
        $_SESSION["login_return"] = $path->full;

        // Load the Lesson
        $l = \Tsugi\UI\LessonsOrchestrator::getLessons($program, $module, $page);

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        if (file_exists($CFG->dirroot . '/../nav.php')) {
            include $CFG->dirroot . '/../nav.php';
        }
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        $l->header();
        echo ('<div class="">');
        $l->render();
        echo ('</div>');
        $OUTPUT->footerStart();
        $l->footer();
        $OUTPUT->footerEnd();
    }

    public static function launch(Application $app, $programAnchor = null, $moduleAnchor = null, $pageAnchor = null, $ltiAnchor = null, $redirectUrl)
    {
        global $CFG;
        // $tsugi = $app['tsugi'];

        $path = U::rest_path();
        $redirect_path = U::addSession($path->parent);
        if ($redirect_path == '') $redirect_path = '/';

        if (!isset($CFG->lessons)) {
            $app->tsugiFlashError(__('Cannot find lessons.json ($CFG->lessons)'));
            return redirect($redirect_path);
        }

        /// Load the Lesson
        $l = \Tsugi\UI\LessonsOrchestrator::getLessons($programAnchor);
        if (!$l) {
            $app->tsugiFlashError(__('Cannot load lessons.'));
            return redirect($redirect_path);
        }

        $module = $l->getModuleByRlid($ltiAnchor);
        if (!$module) {
            $app->tsugiFlashError(__('Cannot find module resource link id'));
            return redirect($redirect_path);
        }

        $lti = $l->getLtiByRlid($ltiAnchor);
        if (!$lti) {
            $app->tsugiFlashError(__('Cannot find lti resource link id'));
            return redirect($redirect_path);
        }

        // Check that the session has the minimums...

        if (
            isset($lti)
            && U::get($_SESSION, 'secret')
            && $l && $module
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

        $resource_link_title = isset($lti->title) ? $lti->title : $module->title;
        $key = isset($_SESSION['oauth_consumer_key']) ? $_SESSION['oauth_consumer_key'] : false;
        $secret = false;
        if (isset($_SESSION['secret'])) {
            $secret = LTIX::decrypt_secret($_SESSION['secret']);
        }
        $context_key = 'course:' . md5("{$programAnchor}_{$module->anchor}");
        $resource_link_id = $lti->resource_link_id;
        $parms = array(
            'lti_message_type' => 'basic-lti-launch-request',
            'resource_link_id' => $resource_link_id,
            'resource_link_title' => $resource_link_title,
            'tool_consumer_info_product_family_code' => 'tsugi',
            'tool_consumer_info_version' => '1.1',
            'context_id' => $context_key,
            'context_label' => $module->title,
            'context_title' => $module->title,
            'user_id' => $_SESSION['user_key'],
            'lis_person_name_full' => $_SESSION['displayname'],
            'lis_person_contact_email_primary' => $_SESSION['email'],
            'roles' => isset($_SESSION["admin"]) ? 'Instructor' : 'Learner',
        );
        if (isset($_SESSION['avatar'])) $parms['user_image'] = $_SESSION['avatar'];

        if (isset($lti->custom)) {
            foreach ($lti->custom as $custom) {
                if (isset($custom->value)) {
                    $parms['custom_' . $custom->key] = $custom->value;
                }
                if (isset($custom->json)) {
                    $parms['custom_' . $custom->key] = json_encode($custom->json);
                }
            }
        }

        // Might need for some cases?
        // $return_url = $path->parent . '/' . str_replace('_launch', '', $path->controller) . '/' . $module->anchor;

        $return_url = "{$CFG->apphome}/programs/{$programAnchor}/{$moduleAnchor}/{$pageAnchor}";

        $parms['launch_presentation_return_url'] = $return_url;

        $sess_key = 'tsugi_top_nav_' . $CFG->wwwroot;
        if (isset($_SESSION[$sess_key])) {
            // $parms['ext_tsugi_top_nav'] = $_SESSION[$sess_key];
        }

        $form_id = "tsugi_form_id_" . bin2Hex(openssl_random_pseudo_bytes(4));
        $parms['ext_lti_form_id'] = $form_id;

        if (isset($lti->external_launch)) {
            $endpoint = $lti->external_launch;
            // $key = "12345";
            // $secret = "secret";
            $key = $CFG->external_store_key;
            $secret = $CFG->external_store_secret;
        } else {
            $endpoint = $lti->launch;
        }

        if (isset($redirectUrl) && strlen($redirectUrl) > 0) {
            $parms['redirect_url'] = $redirectUrl;
        }

        // If auto_register_id exists, set attendee status to 'ATTENDED' for the generated key
        // Then launch tool normally, and it will reflect the change
        if (isset($_SESSION["auto_register_id"])) {
            $regKey = $_SESSION["auto_register_id"];
            try {
                LessonsOrchestrator::autoRegisterAttendee($regKey);
                $_SESSION["auto_register_id"] = null;
            } catch (\Exception $e) {
                return $e->getMessage();
            }
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
}
