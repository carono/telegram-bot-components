<?php


namespace carono\telegram;

use carono\telegram\abs\Command;
use carono\telegram\abs\Model;
use carono\telegram\dto\CallbackQuery;
use carono\telegram\dto\Message;
use carono\telegram\dto\MyChatMember;
use carono\telegram\helpers\StringHelper;
use carono\telegram\traits\CacheTrait;
use Exception;

/**
 * Class Bot
 *
 * @package common\components\telegram
 * @property Message $message
 * @property string $update_id
 * @property MyChatMember $my_chat_member
 * @property CallbackQuery $callback_query
 */
class Bot extends Model
{
    use CacheTrait;

    protected $request;
    protected $client;
    public $token;
    protected $hears = [];
    protected $hearsKeyboard = [];
    public $name;
    public $buttonsFolder;
    public $commandsFolder;

    public $preventHearing = false;

    public function getClient()
    {
        return $this->client ?: $this->client = new \TelegramBot\Api\BotApi($this->token);
    }

    public function reply($message)
    {
        return $this->getClient()->sendMessage($this->message->chat->id, $message);
    }

    public function sayPrivateKeyboard($text, $keyboard)
    {
        if (isset($this->message)) {
            $this->getClient()->sendMessage($this->message->from->id, $text, null, false, null, $keyboard);
        }
        if (isset($this->callback_query)) {
            $this->getClient()->sendMessage($this->callback_query->message->chat->id, $text, null, false, null, $keyboard);
        }
    }

    public function replaceKeyboard($text, $keyboard, $sendIfNotFound = true)
    {
        if (isset($this->message)) {
            try {
                $this->getClient()->editMessageText($this->message->from->id, $this->message->message_id, $text, null, false, $keyboard);
            } catch (\Exception $e) {
                $this->sayPrivateKeyboard($text, $keyboard);
            }
        }
        if (isset($this->callback_query)) {
            try {
                $this->getClient()->editMessageText($this->callback_query->message->chat->id, $this->callback_query->message->message_id, $text, null, false, $keyboard);
            } catch (\Exception $e) {
                $this->sayPrivateKeyboard($text, $keyboard);
            }
        }
    }

    public function say($message)
    {
        if (isset($this->message)) {
            return $this->getClient()->sendMessage($this->message->chat->id, $message);
        }
        if (isset($this->callback_query)) {
            $this->getClient()->answerCallbackQuery($this->callback_query->id);
            return $this->getClient()->sendMessage($this->callback_query->message->chat->id, $message);
        }
        return false;
    }

    public function sayPrivate($message)
    {
        $chatId = $this->getFromId();
        return $this->getClient()->sendMessage($chatId, $message);
    }

    public function getFromId()
    {
        $chatId = null;
        if (isset($this->chat_join_request)) {
            $chatId = $this->chat_join_request->from->id;
        }
        if (isset($this->message)) {
            $chatId = $this->message->from->id;
        }
        if (isset($this->callback_query)) {
            $chatId = $this->callback_query->from->id;
        }
        return $chatId;
    }

    public function ask($message, $closure, $retries = 1)
    {
        $key = json_encode(['ask', $this->getFromId()]);
        $this->say($message);
        $data = ['closure' => $closure, 'retries' => --$retries];
        static::setCacheValue($key, \Opis\Closure\serialize($data));
        return $this;
    }

    public function hearKeyboard($message, $closure, $personally = true)
    {
        $this->hearsKeyboard[$message] = ['message' => $message, 'personally' => $personally, 'closure' => $closure];
    }

    public function hear($message, $closure, $personally = true, $strict = false, $prevent = false)
    {
        $this->hears[] = [
            'message' => $message,
            'personally' => $personally,
            'closure' => $closure,
            'strict' => $strict,
            'prevent' => $prevent
        ];
    }

    public function init()
    {

    }

    public function setRequest($json)
    {
        if (is_string($json)) {
            $json = json_decode($json, true);
        }
        $this->request = $json;
    }

    protected function processCallbackQuery($data)
    {
        $arr = explode('?', $data);
        if (isset($arr[1])) {
            $params = StringHelper::parseQuery($arr[1]);
        } else {
            $params = [];
        }
        $command = explode('/', $arr[0]);
        $button = $command[0];
        $file = $this->buttonsFolder . DIRECTORY_SEPARATOR . StringHelper::camelize($button) . '.php';
        $class = StringHelper::getClassFromFile($file);
        $method = StringHelper::camelize($command[1]);

        if (class_exists($class)) {
            $class::run($method, array_merge([$this], $params));
        }
    }

    protected function registerCommands()
    {
        $dir = $this->commandsFolder;
        foreach (glob("$dir/*.php") as $file) {
            $class = StringHelper::getClassFromFile($file);
            $reflect = new \ReflectionClass($class);
            if (!$reflect->isAbstract() && $reflect->isSubclassOf(Command::class)) {
                $command = new $class;
                call_user_func([$command, 'register'], $this);
            }
        }
    }

    protected function processAskClosure($closureData)
    {
        $key = json_encode(['ask', $this->message->from->id]);

        $closureData = \Opis\Closure\unserialize($closureData);
        if ($closureData['retries'] < 0) {
            static::getCache()->delete($key);
        } else {
            $closureData['retries'] -= 1;
            static::setCacheValue($key, \Opis\Closure\serialize($closureData));
        }
        if (call_user_func($closureData['closure'], $this, $this->message->text ?? '')) {
            static::getCache()->delete($key);
        }
    }

    protected function hearingMessage($data)
    {
        $text = $this->message->text ?? '';
        $message = $data['message'];

        if ($data['personally'] && mb_strpos($text, $this->name, 0, 'UTF-8') === false && $this->message->chat->type !== 'private') {
            return false;
        }

        if (mb_strpos($text, $message, 0, 'UTF-8') !== false || $message === '*') {
            call_user_func($data['closure'], $this);
            if (!empty($data['prevent'])) {
                return true;
            }
        }
        return false;
    }

    protected function processMessage()
    {
        if (empty($this->hears)) {
            throw new Exception('No commands registered for bot ' . $this->name, 'telegram');
        }

        $defaultHears = array_filter($this->hears, function ($item) {
            return $item['message'] != '*';
        });
        $broadcastHears = array_filter($this->hears, function ($item) {
            return $item['message'] == '*';
        });

        foreach ($defaultHears as $data) {
            if ($this->preventHearing) {
                return;
            }
            if ($this->hearingMessage($data) === true) {
                return;
            }
        }

        foreach ($broadcastHears as $data) {
            if ($this->preventHearing) {
                return;
            }
            if ($this->hearingMessage($data) === true) {
                return;
            }
        }
    }

    protected function processJoinRequest()
    {

    }

    protected function beforeRun()
    {

    }


    protected function afterRun()
    {

    }

    protected function run()
    {
        if (!empty($this->chat_join_request)) {
            $this->processJoinRequest();
            return;
        }

        if (!empty($this->callback_query)) {
            $this->processCallbackQuery($this->callback_query->data);
            return;
        }

        if (!empty($this->message) && ($closure = static::getCacheValue(json_encode(['ask', $this->message->from->id])))) {
            $this->processAskClosure($closure);
            return;
        }

        if (!empty($this->message)) {
            $this->processMessage();
        }
    }

    public function process()
    {
        $this->init();
        $this->registerCommands();

        $this->beforeRun();

        $this->run();

        $this->afterRun();
        return '';
    }
}