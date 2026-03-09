<?php
namespace Auth\Form;

use Laminas\Form\Form;
use Laminas\Form\ElementInterface;
use Laminas\Captcha\Figlet;

/**
 * This form is used for forgot password with Captcha
 */
class AuthForm extends Form
{
    /**
     * Constructor.     
     */
    public function __construct()
    {
        // Define form name
        parent::__construct('forgotpwd');
     
        // Set POST method for this form
        $this->setAttribute('method', 'post');
                
        $this->addElements();
        //$this->addInputFilter(); 
    }
    
    /**
     * This method adds elements to form (input fields and submit button).
     */
    protected function addElements() 
    {       
        $this->add([
            'type' => 'Laminas\Form\Element\Captcha',
            'attributes' => [
				'id'   =>'captcha',
				'name' => 'captcha',
				'class' => 'form-control captcha',
			],
            'options' => [
                'label' => 'Please verify you are human',
                'captcha' => new Figlet([
                    'name'    => 'PasswordReset',
                    'wordLen' => 5,
                    'timeout' => 300,
                ]),
            ],
        ]);
	}
}
