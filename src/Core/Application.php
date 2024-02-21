<?php


namespace Koseu\Core;

class Application extends \Tsugi\Lumen\Application {

    public function __construct($launch)
    {
        parent::__construct($launch, __DIR__);
        $this['tsugi']->output->buffer = false;

        $this->router->group([
            'namespace' => 'Koseu\Controllers',
        ], function () {
            \Koseu\Controllers\Lessons::routes($this);
            \Koseu\Controllers\Discussions::routes($this);
            \Koseu\Controllers\Badges::routes($this);
            \Koseu\Controllers\BadgeAdmin::routes($this);
            \Koseu\Controllers\Assignments::routes($this);
            \Koseu\Controllers\Courses::routes($this);
            \Koseu\Controllers\Facilitators::routes($this);
            \Koseu\Controllers\Programs::routes($this);
            \Koseu\Controllers\Registrations::routes($this);
            \Koseu\Controllers\Administration::routes($this);
        });

        $this->router->group([
            'namespace' => 'Tsugi\Controllers',
        ], function () {
            \Tsugi\Controllers\Login::routes($this);
            \Tsugi\Controllers\Logout::routes($this);
            \Tsugi\Controllers\Profile::routes($this);
            \Tsugi\Controllers\Map::routes($this);
        });
    }
}

