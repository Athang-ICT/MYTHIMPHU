<?php
/*
 * Helper -- FlashMessenger View Helper
 * chophel@athang.com 
 */
namespace Application\View\Helper;
use Laminas\View\Helper\AbstractHelper;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;

class FlashMessage extends AbstractHelper
{  
    public function __invoke()
    {     
        $flashMessenger = new FlashMessenger();

        if($flashMessenger->hasMessages())
        {
            $flashMessage = $flashMessenger->getMessages();
            $alertMessages = "";
            foreach ($flashMessage as $message):
                $title = substr($message, 0, strpos($message, '^'));
                $message = strlen($title) > 0 ? substr($message, strpos($message, '^') + 1) : $message;
                $title = strlen($title) > 0 ? $title : 'error';
                $display_title = ucfirst($title);	
                echo <<<EOF
                    <script type="text/javascript">
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-bottom-right",
                            "onclick": null,
                            "showDuration": "1000",
                            "hideDuration": "1000",
                            "timeOut": "8000",
                            "extendedTimeOut": "1000",
                            "showEasing": "swing",
                            "hideEasing": "linear",
                            "showMethod": "fadeIn",
                            "hideMethod": "fadeOut"
                        }
                        toastr.$title("$message", "$display_title");
                    </script>
                EOF;
            endforeach;
        }
    }
}
