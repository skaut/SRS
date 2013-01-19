<?php

/**
 * Homepage presenter.
 */
namespace InstallModule;

class InstallPresenter extends BasePresenter
{

	public function renderDefault()
	{
		$this->template->anyVariable = 'any value';
	}

}
