<?php namespace Klubitus\Facebook;

use Backend;
use RainLab\User\Models\User as UserModel;
use System\Classes\PluginBase;

/**
 * Facebook Plugin Information File
 */
class Plugin extends PluginBase {

    /**
     * Returns information about this plugin.
     *
     * @return  array
     */
    public function pluginDetails() {
        return [
            'name'        => 'Klubitus Facebook',
            'description' => 'Facebook functionality for Klubitus.',
            'author'      => 'Antti Qvickström',
            'icon'        => 'icon-facebook',
            'homepage'    => 'https://github.com/anqqa/klubitus-octobercms-plugins',
        ];
    }


    public function boot() {
        UserModel::extend(function($model) {
            $model->hasMany['user_externals'] = ['Klubitus\Facebook\Models\UserExternal'];
        });
    }


    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents() {
        return [
            'Klubitus\Facebook\Components\FacebookConnect' => 'FacebookConnect',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'klubitus.facebook.some_permission' => [
                'tab' => 'Facebook',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'facebook' => [
                'label'       => 'Facebook',
                'url'         => Backend::url('klubitus/facebook/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['klubitus.facebook.*'],
                'order'       => 500,
            ],
        ];
    }


    public function registerSettings() {
        return [
            'settings' => [
                'label'       => 'Facebook settings',
                'description' => 'Manage Facebook settings.',
                'category'    => 'Klubitus',
                'icon'        => 'icon-facebook',
                'class'       => 'Klubitus\Facebook\Models\Settings',
                'order'       => 900,
            ]
        ];
    }

}
