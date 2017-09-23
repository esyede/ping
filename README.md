**[ABANDONED]** Reason:

Too complicated + route-guessing is awful = Yeah, I'm stupid

![ping framework](assets/images/ping.png)

Lightweight, yet minimalist MVC micro-framework for PHP.

### System requirements
 * PHP 5.6+
 * Webserver (optional, you can use PHP built-in webserver)

### Documentation
Download current documentation [HERE](http://upfile.mobi/CahJMHlfFw7). It's built on top of this framework itself

### Code example:
```php
defined('BASE') or exit('Access denied!');

class Example extends Sys\Core\Controller {


    function __construct() {
        parent::__construct();
        $this->load->lib('template');
    }


    function index() {
        $this->set('data', [
            'layout'=>'example.html',
            'title'=>'Hello World',
            'content'=>'Hello world from ping framework'
        ]);
    }


    function _afterroute() {
        $this->template->render($this->get('data.layout'),$this->get('data'));
    }
}
```
