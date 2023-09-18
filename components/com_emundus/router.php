<?php

defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Component\Router\RouterBase;

class EmundusRouter extends JComponentRouterView
{

	public function __construct($app = null, $menu = null)
	{
		parent::__construct($app, $menu);

		$this->attachRule(new JComponentRouterRulesMenu($this));
		$this->attachRule(new JComponentRouterRulesStandard($this));
		$this->attachRule(new JComponentRouterRulesNomenu($this));
	}

    public function build(&$query)
    {
        $segments = array();
        if (!empty($query['view']) && empty($query['layout']) && empty($query['task']))
        {
	        // This patch helps avoid double opening views. This caused a double refresh on AJAX calls within those views.
	        // SEO was adding the ?view= to links which already had views (ex: emundus.fr/files/?view=files)
	        $v_exceptions = ['files', 'evaluation', 'decision', 'admission', 'users', 'campaigns', 'emails','settings','form'];

	        if (in_array($query['view'], $v_exceptions))
                unset($query['view']);
        }

        return $segments;
    }

    public function parse(&$segments) {}
}
?>