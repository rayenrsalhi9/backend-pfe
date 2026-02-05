<?php

namespace App\Repositories\Implementation;

use App\Models\SendEmails;

use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\SendEmailRepositoryInterface;

//use Your Model

/**
 * Class UserRepository.
 */
class SendEmailRepository extends BaseRepository implements SendEmailRepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor..
     *
     *
     * @param Model $model
     */


    public static function model()
    {
        return SendEmails::class;
    }


    
}
