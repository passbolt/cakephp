<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Mailer;

use BadMethodCallException;
use Cake\Core\StaticConfigTrait;
use Cake\Event\EventListenerInterface;
use Cake\Log\Log;
use Cake\Mailer\Exception\MissingActionException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\View\ViewBuilder;
use InvalidArgumentException;
use function Cake\Core\deprecationWarning;

/**
 * Mailer base class.
 *
 * Mailer classes let you encapsulate related Email logic into a reusable
 * and testable class.
 *
 * ## Defining Messages
 *
 * Mailers make it easy for you to define methods that handle email formatting
 * logic. For example:
 *
 * ```
 * class UserMailer extends Mailer
 * {
 *     public function resetPassword($user)
 *     {
 *         $this
 *             ->setSubject('Reset Password')
 *             ->setTo($user->email)
 *             ->set(['token' => $user->token]);
 *     }
 * }
 * ```
 *
 * Is a trivial example but shows how a mailer could be declared.
 *
 * ## Sending Messages
 *
 * After you have defined some messages you will want to send them:
 *
 * ```
 * $mailer = new UserMailer();
 * $mailer->send('resetPassword', $user);
 * ```
 *
 * ## Event Listener
 *
 * Mailers can also subscribe to application event allowing you to
 * decouple email delivery from your application code. By re-declaring the
 * `implementedEvents()` method you can define event handlers that can
 * convert events into email. For example, if your application had a user
 * registration event:
 *
 * ```
 * public function implementedEvents(): array
 * {
 *     return [
 *         'Model.afterSave' => 'onRegistration',
 *     ];
 * }
 *
 * public function onRegistration(EventInterface $event, EntityInterface $entity, ArrayObject $options)
 * {
 *     if ($entity->isNew()) {
 *          $this->send('welcome', [$entity]);
 *     }
 * }
 * ```
 *
 * The onRegistration method converts the application event into a mailer method.
 * Our mailer could either be registered in the application bootstrap, or
 * in the Table class' initialize() hook.
 *
 * @method $this setTo($email, $name = null) Sets "to" address. {@see \Cake\Mailer\Message::setTo()}
 * @method array getTo() Gets "to" address. {@see \Cake\Mailer\Message::getTo()}
 * @method $this setFrom($email, $name = null) Sets "from" address. {@see \Cake\Mailer\Message::setFrom()}
 * @method array getFrom() Gets "from" address. {@see \Cake\Mailer\Message::getFrom()}
 * @method $this setSender($email, $name = null) Sets "sender" address. {@see \Cake\Mailer\Message::setSender()}
 * @method array getSender() Gets "sender" address. {@see \Cake\Mailer\Message::getSender()}
 * @method $this setReplyTo($email, $name = null) Sets "Reply-To" address. {@see \Cake\Mailer\Message::setReplyTo()}
 * @method array getReplyTo() Gets "Reply-To" address. {@see \Cake\Mailer\Message::getReplyTo()}
 * @method $this addReplyTo($email, $name = null) Add "Reply-To" address. {@see \Cake\Mailer\Message::addReplyTo()}
 * @method $this setReadReceipt($email, $name = null) Sets Read Receipt (Disposition-Notification-To header).
 *   {@see \Cake\Mailer\Message::setReadReceipt()}
 * @method array getReadReceipt() Gets Read Receipt (Disposition-Notification-To header).
 *   {@see \Cake\Mailer\Message::getReadReceipt()}
 * @method $this setReturnPath($email, $name = null) Sets return path. {@see \Cake\Mailer\Message::setReturnPath()}
 * @method array getReturnPath() Gets return path. {@see \Cake\Mailer\Message::getReturnPath()}
 * @method $this addTo($email, $name = null) Add "To" address. {@see \Cake\Mailer\Message::addTo()}
 * @method $this setCc($email, $name = null) Sets "cc" address. {@see \Cake\Mailer\Message::setCc()}
 * @method array getCc() Gets "cc" address. {@see \Cake\Mailer\Message::getCc()}
 * @method $this addCc($email, $name = null) Add "cc" address. {@see \Cake\Mailer\Message::addCc()}
 * @method $this setBcc($email, $name = null) Sets "bcc" address. {@see \Cake\Mailer\Message::setBcc()}
 * @method array getBcc() Gets "bcc" address. {@see \Cake\Mailer\Message::getBcc()}
 * @method $this addBcc($email, $name = null) Add "bcc" address. {@see \Cake\Mailer\Message::addBcc()}
 * @method $this setCharset($charset) Charset setter. {@see \Cake\Mailer\Message::setCharset()}
 * @method string getCharset() Charset getter. {@see \Cake\Mailer\Message::getCharset()}
 * @method $this setHeaderCharset($charset) HeaderCharset setter. {@see \Cake\Mailer\Message::setHeaderCharset()}
 * @method string getHeaderCharset() HeaderCharset getter. {@see \Cake\Mailer\Message::getHeaderCharset()}
 * @method $this setSubject($subject) Sets subject. {@see \Cake\Mailer\Message::setSubject()}
 * @method string getSubject() Gets subject. {@see \Cake\Mailer\Message::getSubject()}
 * @method $this setHeaders(array $headers) Sets headers for the message. {@see \Cake\Mailer\Message::setHeaders()}
 * @method $this addHeaders(array $headers) Add header for the message. {@see \Cake\Mailer\Message::addHeaders()}
 * @method $this getHeaders(array $include = []) Get list of headers. {@see \Cake\Mailer\Message::getHeaders()}
 * @method $this setEmailFormat($format) Sets email format. {@see \Cake\Mailer\Message::setEmailFormat()}
 * @method string getEmailFormat() Gets email format. {@see \Cake\Mailer\Message::getEmailFormat()}
 * @method $this setMessageId($message) Sets message ID. {@see \Cake\Mailer\Message::setMessageId()}
 * @method string|bool getMessageId() Gets message ID. {@see \Cake\Mailer\Message::getMessageId()}
 * @method $this setDomain($domain) Sets domain. {@see \Cake\Mailer\Message::setDomain()}
 * @method string getDomain() Gets domain. {@see \Cake\Mailer\Message::getDomain()}
 * @method $this setAttachments($attachments) Add attachments to the email message. {@see \Cake\Mailer\Message::setAttachments()}
 * @method array getAttachments() Gets attachments to the email message. {@see \Cake\Mailer\Message::getAttachments()}
 * @method $this addAttachments($attachments) Add attachments. {@see \Cake\Mailer\Message::addAttachments()}
 * @method array|string getBody(?string $type = null) Get generated message body as array.
 *   {@see \Cake\Mailer\Message::getBody()}
 */
class Mailer implements EventListenerInterface
{
    use LocatorAwareTrait;
    use StaticConfigTrait;

    /**
     * Mailer's name.
     *
     * @var string
     */
    public static string $name;

    /**
     * The transport instance to use for sending mail.
     *
     * @var \Cake\Mailer\AbstractTransport|null
     */
    protected ?AbstractTransport $transport = null;

    /**
     * Message class name.
     *
     * @var string
     * @phpstan-var class-string<\Cake\Mailer\Message>
     */
    protected string $messageClass = Message::class;

    /**
     * Message instance.
     *
     * @var \Cake\Mailer\Message
     */
    protected Message $message;

    /**
     * Email Renderer
     *
     * @var \Cake\Mailer\Renderer|null
     */
    protected ?Renderer $renderer = null;

    /**
     * Hold message, renderer and transport instance for restoring after running
     * a mailer action.
     *
     * @var array<string, mixed>
     */
    protected array $clonedInstances = [
        'message' => null,
        'renderer' => null,
        'transport' => null,
    ];

    /**
     * Mailer driver class map.
     *
     * @var array<string, string>
     * @phpstan-var array<string, class-string>
     */
    protected static array $_dsnClassMap = [];

    /**
     * @var array|null
     */
    protected ?array $logConfig = null;

    /**
     * Constructor
     *
     * @param array<string, mixed>|string|null $config Array of configs, or string to load configs from app.php
     */
    public function __construct(array|string|null $config = null)
    {
        $this->message = new $this->messageClass();

        $config ??= static::getConfig('default');

        if ($config) {
            $this->setProfile($config);
        }
    }

    /**
     * Get the view builder.
     *
     * @return \Cake\View\ViewBuilder
     */
    public function viewBuilder(): ViewBuilder
    {
        return $this->getRenderer()->viewBuilder();
    }

    /**
     * Get email renderer.
     *
     * @return \Cake\Mailer\Renderer
     */
    public function getRenderer(): Renderer
    {
        return $this->renderer ??= new Renderer();
    }

    /**
     * Set email renderer.
     *
     * @param \Cake\Mailer\Renderer $renderer Render instance.
     * @return $this
     */
    public function setRenderer(Renderer $renderer)
    {
        $this->renderer = $renderer;

        return $this;
    }

    /**
     * Get message instance.
     *
     * @return \Cake\Mailer\Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * Set message instance.
     *
     * @param \Cake\Mailer\Message $message Message instance.
     * @return $this
     * @deprecated 5.1.0 Configure the mailer according to the documentation instead of manually setting the Message instance.
     */
    public function setMessage(Message $message)
    {
        deprecationWarning(
            '5.1.0',
            'Setting the message instance is deprecated. Configure the mailer according to the documentation instead.',
        );
        $this->message = $message;

        return $this;
    }

    /**
     * Magic method to forward method class to Message instance.
     *
     * @param string $method Method name.
     * @param array $args Method arguments
     * @return $this|mixed
     */
    public function __call(string $method, array $args)
    {
        $result = $this->message->$method(...$args);
        if (str_starts_with($method, 'get')) {
            return $result;
        }

        return $this;
    }

    /**
     * Sets email view vars.
     *
     * @param array|string $key Variable name or hash of view variables.
     * @param mixed $value View variable value.
     * @return $this
     */
    public function setViewVars(array|string $key, mixed $value = null)
    {
        $this->getRenderer()->set($key, $value);

        return $this;
    }

    /**
     * Sends email.
     *
     * If an `$action` is specified the internal state of the mailer will be
     * backed up and restored after the action is run.
     *
     * @param string|null $action The name of the mailer action to trigger.
     *   If no action is specified then all other method arguments will be ignored.
     * @param array $args Arguments to pass to the triggered mailer action.
     * @param array $headers Headers to set.
     * @return array
     * @throws \Cake\Mailer\Exception\MissingActionException
     * @throws \BadMethodCallException
     * @phpstan-return array{headers: string, message: string}
     */
    public function send(?string $action = null, array $args = [], array $headers = []): array
    {
        if ($action === null) {
            return $this->deliver();
        }

        if (!method_exists($this, $action)) {
            throw new MissingActionException([
                'mailer' => static::class,
                'action' => $action,
            ]);
        }

        $this->backup();

        $this->getMessage()->setHeaders($headers);
        if (!$this->viewBuilder()->getTemplate()) {
            $this->viewBuilder()->setTemplate($action);
        }

        try {
            $this->$action(...$args);

            $result = $this->deliver();
        } finally {
            $this->restore();
        }

        return $result;
    }

    /**
     * Render content and set message body.
     *
     * @param string $content Content.
     * @return $this
     */
    public function render(string $content = '')
    {
        $content = $this->getRenderer()->render(
            $content,
            $this->message->getBodyTypes(),
        );

        $this->message->setBody($content);

        return $this;
    }

    /**
     * Render content and send email using configured transport.
     *
     * @param string $content Content.
     * @return array
     * @phpstan-return array{headers: string, message: string}
     */
    public function deliver(string $content = ''): array
    {
        $this->render($content);

        $result = $this->getTransport()->send($this->message);
        $this->logDelivery($result);

        return $result;
    }

    /**
     * Sets the configuration profile to use for this instance.
     *
     * @param array<string, mixed>|string $config String with configuration name, or
     *    an array with config.
     * @return $this
     */
    public function setProfile(array|string $config)
    {
        if (is_string($config)) {
            $name = $config;
            $config = static::getConfig($name);
            if (!$config) {
                throw new InvalidArgumentException(sprintf('Unknown email configuration `%s`.', $name));
            }
            unset($name);
        }

        $simpleMethods = [
            'transport',
        ];
        foreach ($simpleMethods as $method) {
            if (isset($config[$method])) {
                $this->{'set' . ucfirst($method)}($config[$method]);
                unset($config[$method]);
            }
        }

        $viewBuilderMethods = [
            'template', 'layout', 'theme',
        ];
        foreach ($viewBuilderMethods as $method) {
            if (array_key_exists($method, $config)) {
                $this->viewBuilder()->{'set' . ucfirst($method)}($config[$method]);
                unset($config[$method]);
            }
        }

        if (array_key_exists('helpers', $config)) {
            $this->viewBuilder()->setHelpers($config['helpers']);
            unset($config['helpers']);
        }
        if (array_key_exists('viewRenderer', $config)) {
            $this->viewBuilder()->setClassName($config['viewRenderer']);
            unset($config['viewRenderer']);
        }
        if (array_key_exists('viewVars', $config)) {
            $this->viewBuilder()->setVars($config['viewVars']);
            unset($config['viewVars']);
        }
        if (isset($config['autoLayout'])) {
            if ($config['autoLayout'] === false) {
                $this->viewBuilder()->disableAutoLayout();
            }
            unset($config['autoLayout']);
        }

        if (isset($config['log'])) {
            $this->setLogConfig($config['log']);
        }

        $this->message->setConfig($config);

        return $this;
    }

    /**
     * Sets the transport.
     *
     * When setting the transport you can either use the name
     * of a configured transport or supply a constructed transport.
     *
     * @param \Cake\Mailer\AbstractTransport|string $name Either the name of a configured
     *   transport, or a transport instance.
     * @return $this
     * @throws \LogicException When the chosen transport lacks a send method.
     */
    public function setTransport(AbstractTransport|string $name)
    {
        if (is_string($name)) {
            $this->transport = TransportFactory::get($name);
        } else {
            $this->transport = $name;
        }

        return $this;
    }

    /**
     * Gets the transport.
     *
     * @return \Cake\Mailer\AbstractTransport
     */
    public function getTransport(): AbstractTransport
    {
        if ($this->transport === null) {
            throw new BadMethodCallException(
                'Transport was not defined. '
                . 'You must set on using setTransport() or set `transport` option in your mailer profile.',
            );
        }

        return $this->transport;
    }

    /**
     * Backup message, renderer, transport instances before an action is run.
     *
     * @return void
     */
    protected function backup(): void
    {
        $this->clonedInstances['message'] = clone $this->message;
        if ($this->renderer !== null) {
            $this->clonedInstances['renderer'] = clone $this->renderer;
        }
        if ($this->transport !== null) {
            $this->clonedInstances['transport'] = clone $this->transport;
        }
    }

    /**
     * Restore message, renderer, transport instances to state before an action was run.
     *
     * @return $this
     */
    protected function restore()
    {
        foreach (array_keys($this->clonedInstances) as $key) {
            if ($this->clonedInstances[$key] === null) {
                if ($key === 'message') {
                    $this->message->reset();
                } else {
                    $this->{$key} = null;
                }
            } else {
                $this->{$key} = clone $this->clonedInstances[$key];
                $this->clonedInstances[$key] = null;
            }
        }

        return $this;
    }

    /**
     * Reset all the internal variables to be able to send out a new email.
     *
     * @return $this
     */
    public function reset()
    {
        $this->message->reset();
        $this->getRenderer()->reset();
        $this->transport = null;
        $this->clonedInstances = [
            'message' => null,
            'renderer' => null,
            'transport' => null,
        ];

        return $this;
    }

    /**
     * Log the email message delivery.
     *
     * @param array $contents The content with 'headers' and 'message' keys.
     * @return void
     * @phpstan-param array{headers: string, message: string} $contents
     */
    protected function logDelivery(array $contents): void
    {
        if (!$this->logConfig) {
            return;
        }

        Log::write(
            $this->logConfig['level'],
            PHP_EOL . $this->flatten($contents['headers']) . PHP_EOL . PHP_EOL . $this->flatten($contents['message']),
            $this->logConfig['scope'],
        );
    }

    /**
     * Set logging config.
     *
     * @param array<string, mixed>|string|true $log Log config.
     * @return void
     */
    protected function setLogConfig(array|string|bool $log): void
    {
        $config = [
            'level' => 'debug',
            'scope' => ['cake.mailer', 'email'],
        ];
        if ($log !== true) {
            if (!is_array($log)) {
                $log = ['level' => $log];
            }
            $config = $log + $config;
        }

        $this->logConfig = $config;
    }

    /**
     * Converts given value to string
     *
     * @param array<string>|string $value The value to convert
     * @return string
     */
    protected function flatten(array|string $value): string
    {
        return is_array($value) ? implode(';', $value) : $value;
    }

    /**
     * Implemented events.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [];
    }
}
