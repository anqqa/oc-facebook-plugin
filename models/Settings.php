<?php namespace Klubitus\Facebook\Models;

use Model;

/**
 * Settings Model
 */
class Settings extends Model {

    public $implement = ['System.Behaviors.SettingsModel'];

    // A unique code
    public $settingsCode = 'klubitus_facebook_settings';

    // Reference to field configuration
    public $settingsFields = 'fields.yaml';

}
