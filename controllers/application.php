<?php

class Application extends Phpr_Controller
{
	protected $global_handlers = array('on_handle_request');

	protected function resolve_page(&$params)
	{
		$page = null;
		$params = null;
		$action = strtolower(Phpr::$request->get_current_uri());

		$page = $page ? $page : Cms_Page::get_url($action, $params);

		if (!$page)
		{
			$page = Cms_Page::get_url('/404', $params);
			if ($page)
				header("HTTP/1.0 404 Not Found");
		}

		if (!$page)
			$page = Cms_Page::get_url('/', $params);

		if (!$page)
			die("We're sorry, a page with the specified address could not be found.");

		return $page;
	}

	protected function on_handle_request()
	{
		Cms_Controller::create()->handle_ajax_request(
			$this->resolve_page($params), 
			post('cms_handler_name'), 
			post('cms_update_elements', 
			array()), 
			$params
		);
	}
	
	public function on_404()
	{
		// Check for access points
		if ($this->check_access_points())
			return;

		// Open page
		$params = array();
		$controller = Cms_Controller::create();
		$controller->open($this->resolve_page($params), $params);
	}

	public function on_exception($exception)
	{
		if ($controller = Cms_Controller::get_instance())
		{
			$controller->display_exception($this, $exception);
		}
		else
		{
			$this->set_views_path('modules/cms/error_pages');
			$handlers = ob_list_handlers();
			foreach ($handlers as $handler)
			{
				if (strpos($handler, 'zlib') === false)
					ob_end_clean();
			}

			if (!Phpr::$request->is_remote_event())
			{
				$this->view_data['error'] = Phpr_Error_Log::get_exception_details($exception);
				$this->load_view('exception', false, true);
			}
			else
			{
				Phpr::$response->ajax_report_exception($exception, true);
			}
		}
	}

	private function check_access_points()
	{
		try
		{
			$action = substr(Phpr::$request->get_current_uri(), 1);

			$raw_url_parts = explode('/', $action);
			$uri_parts = array();
			foreach ($raw_url_parts as $part)
			{
				if (strlen($part))
					$uri_parts[] = $part;
			}

			if (!$uri_parts)
				return false;

			$action = mb_strtolower(array_shift($uri_parts));

			$modules = Phpr_Module_Manager::get_modules();
			foreach ($modules as $module)
			{
				$points = $module->subscribe_access_points($action);

				if (!is_array($points))
					continue;

				foreach ($points as $url=>$method)
				{
					if ($url != $action)
						continue;

					// Support for array($obj, 'method')
					if (is_array($method) && count($method) > 1)
					{
						$obj = $method[0];
						$method = $method[1];
						$obj->$method($uri_parts);
						return true;
					}

					// Support for 'classname::method'
					if (strpos($method, '::'))
					{
						$parts = explode('::', $method);
						$class_name = $parts[0];
						$method = $parts[1];
						$obj = new $class_name();
						$obj->$method($uri_parts);
						return true;
					}

					// Local method
					$module->$method($uri_parts);
					return true;
				}
			}
		}
		catch (Exception $ex)
		{
			$controller = Cms_Controller::create();
			$this->on_exception($ex);
			return true;
		}

		return false;
	}

	/**
	 * @deprecated
	 */
	public function OnException() { return $this->on_exception(); }
	public function On404() { return $this->on_404(); }    
}
