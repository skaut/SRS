<?php

/**
 * Homepage presenter.
 */
namespace BackModule;
class DashboardPresenter extends BasePresenter
{

	public function renderDefault()
	{
		$this->template->anyVariable = 'any value';
	}

}
