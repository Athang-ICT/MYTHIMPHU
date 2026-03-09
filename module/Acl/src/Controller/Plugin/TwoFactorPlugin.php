<?php
namespace Acl\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Session\Container;
use Laminas\Authentication\AuthenticationService;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp;
use Laminas\Mail\Transport\SmtpOptions;

class TwoFactorPlugin extends AbstractPlugin
{
    protected $dbAdapter;
    protected $passwordPlugin;
    

    // Accept container and get dependencies internally
    public function __construct($container)
    {
        $this->dbAdapter = $container->get('Laminas\Db\Adapter\Adapter');
        $this->passwordPlugin = $container->get('ControllerPluginManager')->get('password');
    }

    public function initiate($user)
    {
        $twoFaCode = random_int(100000, 999999);
        $session = new Container('2fa');
        $session->userId = $user->id;
        $session->code = $twoFaCode;
        $session->expires = time() + 300; // 5 mins
        $this->sendCodeEmail($user->email, $user->name, $twoFaCode);
    }

    protected function sendCodeEmail($email, $name, $code)
    {
        $message = new Message();
        $message->addTo($email)
                ->setFrom('yaezerl@gmail.com', 'Dheyma Tourism System')
                ->setSubject('Your 2FA Code')
                ->setBody("Your code is: $code");

        $transport = new Smtp();
        $options = new SmtpOptions([
            'name' => 'smtp.gmail.com',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'connection_class' => 'login',
            'connection_config' => [
                'username' => 'yaezerl@gmail.com',
                'password' => 'brjv ccel azap spdn',
                'ssl' => 'tls',
            ],
        ]);
        $transport->setOptions($options);
        $transport->send($message);
    }

    public function verify($code)
    {
        $session = new Container('2fa');
        $auth = new \Laminas\Authentication\AuthenticationService();

        if (empty($session->userId)) {
            return ['success' => false, 'message' => 'Session expired, please login again.'];
        }
        if (time() > $session->expires) {
            $this->clear();
            return ['success' => false, 'message' => '2FA code expired, please login again.'];
        }
        if ($code == $session->code) {
            $user = $session->user;

            // ⭐ Write user to auth storage (so views/helpers work)
            $auth->getStorage()->write($user);

            $this->clear();
            return ['success' => true, 'user' => $user];
        }
        return ['success' => false, 'message' => 'Invalid 2FA code, try again.'];
    }

}
