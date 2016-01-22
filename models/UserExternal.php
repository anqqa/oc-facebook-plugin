<?php namespace Klubitus\Facebook\Models;

use Model;
use October\Rain\Database\QueryBuilder;

/**
 * UserExternal Model
 */
class UserExternal extends Model {
    const PROVIDER_FACEBOOK = 'facebook';

    /**
     * @var  string  The database table used by the model.
     */
    public $table = 'user_externals';

    /**
     * @var  array  Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var  array  Fillable fields
     */
    protected $fillable = [
        'user_id', 'provider', 'token', 'external_user_id', 'expires_at', 'settings'
    ];

    protected $dates = ['expires_at'];

    /**
     * @var  array  Relations
     */
    public $belongsTo = [
        'user' => 'RainLab\User\Models\User',
    ];


    /**
     * Get events by Facebook event id.
     *
     * @param   QueryBuilder  $query
     * @param   array         $facebookIds
     * @return  QueryBuilder
     */
    public function scopeFacebook($query, array $facebookIds) {
        return $query->where('provider', '=', self::PROVIDER_FACEBOOK)
            ->whereIn('external_user_id', $facebookIds);
    }


}
