<?php


namespace carono\telegram\dto;

use carono\telegram\abs\Model;

/**
 * Class Message
 *
 * @package common\components\telegram
 * @property Chat $chat
 * @property From $from
 * @property integer $message_id
 * @property integer $date
 * @property string $text
 * @property Contact $contact
 */
class Message extends Model
{
}