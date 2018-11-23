<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $table = 'member';
    protected $fillable = [
        'card','wxname','wxsex','openid','headimg','phone','status','username','sex','additional','level','tag',
        'comment','status_reason','created_at','updated_at','extend_id','unionid','cardno'
    ];
}