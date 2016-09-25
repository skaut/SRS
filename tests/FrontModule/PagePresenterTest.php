<?php

namespace SRS\Test;

class PagePresenterTest extends \PHPUnit_Framework_TestCase
{

	protected function setUp()
	{
		parent::setUp();

	}

    public function testRenderDefault()
    {
        $context = \Nette\Environment::getContext();
        $presenter = new \FrontModule\PagePresenter($context);
        $request = new \Nette\Application\Request('front:page', 'GET', array('pageId' => null));
        $response = $presenter->run($request);
        $this->assertInstanceOf(
            'Nette\Application\Responses\TextResponse',
            $response
        );
    }

}