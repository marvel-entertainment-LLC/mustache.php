<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2012 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @group lambdas
 * @group functional
 */
class Mustache_Test_FiveThree_Functional_LambdaHelperTest extends PHPUnit_Framework_TestCase
{
    private $mustache;

    public function setUp()
    {
        $this->mustache = new Mustache_Engine;
    }

    public function testSectionLambdaHelper()
    {
        $one = $this->mustache->loadTemplate('{{name}}');
        $two = $this->mustache->loadTemplate('{{#lambda}}{{name}}{{/lambda}}');

        $foo = new StdClass;
        $foo->name = 'Mario';
        $foo->lambda = function($text, $mustache) {
            return strtoupper($mustache->render($text));
        };

        $this->assertEquals('Mario', $one->render($foo));
        $this->assertEquals('MARIO', $two->render($foo));
    }

    public function testFind() {
        $context = new Mustache_Context(array(
            'foo' => 1,
            'bar' => 'b',
            'baz' => array(
                'qux' => 'WIN'
            )
        ));
        $context->push('MOAR WIN');

        $helper = new Mustache_LambdaHelper($this->mustache, $context);
        $this->assertEquals(1, $helper->find('foo'));
        $this->assertEquals('b', $helper->find('bar'));
        $this->assertEquals('WIN', $helper->find('baz.qux'));
        $this->assertEquals('MOAR WIN', $helper->find('.'));
    }
}
