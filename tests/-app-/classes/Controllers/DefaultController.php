<?php
/**
 * spiral
 *
 * @author    Wolfy-J
 */

namespace TestApplication\Controllers;

use Spiral\Core\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return "Hello, Dave.";
    }
}