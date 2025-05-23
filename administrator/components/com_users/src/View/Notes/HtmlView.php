<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_users
 *
 * @copyright   (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Users\Administrator\View\Notes;

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Button\DropdownButton;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\User\User;
use Joomla\Component\Users\Administrator\Model\NotesModel;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * User notes list view
 *
 * @since  2.5
 */
class HtmlView extends BaseHtmlView
{
    /**
     * A list of user note objects.
     *
     * @var    array
     * @since  2.5
     */
    protected $items;

    /**
     * The pagination object.
     *
     * @var    \Joomla\CMS\Pagination\Pagination
     * @since  2.5
     */
    protected $pagination;

    /**
     * The model state.
     *
     * @var    \Joomla\Registry\Registry
     * @since  2.5
     */
    protected $state;

    /**
     * The model state.
     *
     * @var    User
     * @since  2.5
     */
    protected $user;

    /**
     * Form object for search filters
     *
     * @var    \Joomla\CMS\Form\Form
     *
     * @since  4.0.0
     */
    public $filterForm;

    /**
     * The active search filters
     *
     * @var    array
     * @since  4.0.0
     */
    public $activeFilters;

    /**
     * Is this view an Empty State
     *
     * @var  boolean
     * @since 4.0.0
     */
    private $isEmptyState = false;

    /**
     * Override the display method for the view.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     *
     * @since   2.5
     */
    public function display($tpl = null)
    {
        /** @var NotesModel $model */
        $model = $this->getModel();

        // Initialise view variables.
        $this->items         = $model->getItems();
        $this->pagination    = $model->getPagination();
        $this->state         = $model->getState();
        $this->user          = $model->getUser();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();

        if (!\count($this->items) && $this->isEmptyState = $model->getIsEmptyState()) {
            $this->setLayout('emptystate');
        }

        // Check for errors.
        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // Turn parameters into registry objects
        foreach ($this->items as $item) {
            $item->cparams = new Registry($item->category_params);
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    /**
     * Display the toolbar.
     *
     * @return  void
     *
     * @since   2.5
     */
    protected function addToolbar()
    {
        $canDo   = ContentHelper::getActions('com_users', 'category', $this->state->get('filter.category_id'));
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_USERS_VIEW_NOTES_TITLE'), 'users user');

        if ($canDo->get('core.create')) {
            $toolbar->addNew('note.add');
        }

        if (!$this->isEmptyState && ($canDo->get('core.edit.state') || $canDo->get('core.admin'))) {
            /** @var DropdownButton $dropdown */
            $dropdown = $toolbar->dropdownButton('status-group', 'JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();

            if ($canDo->get('core.edit.state')) {
                $childBar->publish('notes.publish')->listCheck(true);
                $childBar->unpublish('notes.unpublish')->listCheck(true);
                $childBar->archive('notes.archive')->listCheck(true);
                $childBar->checkin('notes.checkin')->listCheck(true);
            }

            if ($this->state->get('filter.published') != -2 && $canDo->get('core.edit.state')) {
                $childBar->trash('notes.trash');
            }
        }

        if (!$this->isEmptyState && $this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
            $toolbar->delete('notes.delete', 'JTOOLBAR_DELETE_FROM_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($canDo->get('core.admin') || $canDo->get('core.options')) {
            $toolbar->preferences('com_users');
        }

        $toolbar->help('User_Notes');
    }
}
