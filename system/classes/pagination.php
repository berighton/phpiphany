<?php
/**
 * Pagination class that displays results broken down by page
 * Works with any entity - arrays, db results, cache
 *
 * This class is self-sustained meaning although it does not strictly follow the MVC pattern,
 * it can be ported to a procedural application and used just the same
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package system/classes
 * @since 1.0
 *
 */


class pagination {

	// Limit - number of items per page; and a current page
	private $limit, $page;
	// Options to change the number of items per page
	private $limits = array(10,20,50,100,'All');
	// Total number of items
	private $total;
	// Number of pages (clickable links)
	private $num_pages;
	// number of selectable pages
	private $selectable_pages = 11;
	// Final output string (HTML formatted)
	private $html;
	// Full URL to which append the page numbers
	private $url;

	/**
	 * Initialize system variables, probing for the GET[] parameters
	 *
	 * @param int $page The current page
	 * @param int $limit Items per page
	 * @param int $total Count of total entities
	 */
	public function __construct($page = 1, $limit = 10, $total = 0){
		$this->page = $page;
		$this->limit = $limit;
		$this->total = $total;
		$this->url = full_url(true);
	}

	/**
	 * Generates the pagination bar with '...' in between if total is greater than selectable_pages
	 * 
	 * @return string Formatted HTML
	 */
	public function paginate(){
		// Set number of pages to generate. Quit on error
		$this->num_pages = ($this->limit == 'All')? $this->num_pages = 1 : ceil($this->total/$this->limit);
		if ($this->num_pages < 1) return '';
		
		// Make some adjustments to the current page number and set prev/next
		if (!is_numeric($this->page)) $this->page = 1;
		if ($this->page < 1) {
			$this->page = 1;
			$prev_page = $this->page;
			$next_page = ++$this->page;
		} elseif ($this->page > $this->num_pages) {
			$this->page = $this->num_pages + 1;
			$prev_page = --$this->page;
			$next_page = $this->page;
		} else {
			$prev_page = $this->page - 1;
			$next_page = $this->page + 1;
		}

		// To make a clean URL, do not print limit if it is set to the default value
		$show_limit = $this->limit != 10? "&amp;limit=$this->limit" : '';

		// Start building the pages bar
		$pages = '			<div class="pagination">
				<ul>
';

		// If we need to display '...' between pages
		if ($this->num_pages > 10) {
			// 'Prev' button. Disabled if this is the first page
			if ($this->page > 1 and $this->total > $this->selectable_pages) $pages .= "					<li><a href=\"{$this->url}?page={$prev_page}{$show_limit}\">&laquo; Prev</a>";
			else $pages .= '					<li class="disabled"><a href="javascript:void(0)">&laquo; Prev</a>';
			$pages .= "</li>\n";

			// Make a link to the first page (if we have pagination)
			$pages .= '					<li';
			$pages .= ($this->page == 1)? " class=\"active\"><a href=\"javascript:void(0)\">1</a></li>\n" :
										"><a href=\"{$this->url}?page=1{$show_limit}\">1</a></li>\n";

			// Compute the number of pages to display to the left of the currently selected page so that the current page is always centered
			// (when at first or the last pages, this will not be possible and we'll make some adjustments on the fly)
			$adjacent = floor(($this->selectable_pages - 3) / 2);
			// This number must be at least "1"
			$adjacent = ($adjacent == 0 ? 1 : $adjacent);
			// Compute the page after which to show "..." after the link to the first page
			$scroll_from = $this->selectable_pages - $adjacent;
			// This is the number from where we should start rendering selectable pages. It is set to "2" because we already rendered the first page
			$starting_page = 2;

			// If we need to show "..." after the link to the first page
			if ($this->page >= $scroll_from) {
				// By default, the starting_page should be whatever the current page minus $adjacent
				$starting_page = $this->page - $adjacent;
				// But if that would cause us to display less navigation links than specified in $this->selectable_pages
				if ($this->num_pages - $starting_page < ($this->selectable_pages - 2)) {
					// adjust it
					$starting_page -= ($this->selectable_pages - 2) - ($this->num_pages - $starting_page);
				}
				// put the "..." after the link to the first page
				$pages .= "				<li class=\"disabled\"><a href=\"javascript:void(0)\">&hellip;</a></li>\n";
			}

			// This is the number where we should stop rendering selectable pages. By default, this value is the sum of
			// the starting page plus whatever the number of $this->selectable_pages minus 3 (first page, last page and current page)
			$ending_page = $starting_page + $this->selectable_pages - 3;

			// If ending page would be greater than the total number of pages minus 1,
			// (minus one because we don't take into account the very last page which we output automatically)
			// adjust the ending page
			if ($ending_page > $this->num_pages - 1) {
				$ending_page = $this->num_pages - 1;
			}

			// Make links for every page
			for ($i = $starting_page; $i <= $ending_page; $i++) {
				$pages .= '				<li';
				$pages .= ($this->page == $i)? " class=\"active\"><a href=\"javascript:void(0)\">$i</a></li>\n" :
											"><a href=\"{$this->url}?page={$i}{$show_limit}\">$i</a></li>\n";
			}

			// Place the "..." before the link to the last page, if it is needed
			if ($this->num_pages - $ending_page > 1) {
				$pages .= "				<li class=\"disabled\"><a href=\"javascript:void(0)\">&hellip;</a></li>\n";
			}

			// Make a link to the last page
			$pages .= '				<li';
			$pages .= ($this->page == $i)? " class=\"active\"><a href=\"javascript:void(0)\">$this->num_pages</a></li>\n" :
										"><a href=\"{$this->url}?page={$this->num_pages}{$show_limit}\">$this->num_pages</a></li>\n";

			// If the total number of available pages is greater than the number of pages to be displayed at once it means we can show the "next page" link
			if ($this->num_pages > $this->selectable_pages) {
				$pages .= ($this->page == $this->num_pages)? '				<li class="disabled"><a href="javascript:void(0)">Next &raquo;</a>' . "</li>\n" :
														"				<li><a href=\"{$this->url}?page={$next_page}{$show_limit}\">Next &raquo;</a></li>\n";
			}
		// Otherwise simply render all the pages without prev/next
		} elseif ($this->num_pages > 1) {
			for ($i = 1; $i <= $this->num_pages; $i++) {
				$pages .= '					';
				$pages .= ($i == $this->page) ? '<li class="active"><a href="javascript:void(0)">' . $i . '</a>' : "<li><a href=\"{$this->url}?page={$i}{$show_limit}\">{$i}</a>";
				$pages .= "</li>\n";
			}
		}

		$pages .= "				</ul>\n";

		if ($this->num_pages > 1) {
			// Finally display the option to select a different amount of item-per-page (limits)
			$options = '';
			foreach ($this->limits as $ipp) {
				$options .= '						';
				$options .= ($ipp == $this->limit) ? "<option selected value=\"$ipp\">$ipp</option>" : "<option value=\"$ipp\">$ipp</option>";
				$options .= "\n";
			}

			$pages .= "				<span class=\"right\"> View: \n";
			$pages .= "					<select style=\"margin-top:5px\" class=\"span1\" onchange=\"window.location='{$this->url}?page={$this->page}&amp;limit='+this[this.selectedIndex].value; return false\">\n$options";
			$pages .= "					</select>\n				</span>\n";
		}

		$pages .= "			</div>\n			";

		return $pages;
	}

	/**
	 * Renders the passed entities in a wrapper with page numbers on top and bottom
	 *
	 * @param array $entities The entities array. Either pip entities or a simply array that we need to paginate
	 * @param mixed $custom_view Load a custom view for this list of entities (a new file has to exist in views/entities/list_xxx.php)
	 * @return string Returns the HTML formatted output
	 */
	public function render(array $entities, $custom_view){
		global $view;
		$this->html = '		<div class="content-wrapper">' . "\n";
		$this->html .= $this->paginate();
		if (!$custom_view) $this->html .= $view->load('entities/list', array('assets_dir' => $view->assets_dir, 'entities' => $entities));
		else $this->html .= $view->load('entities/list_' . $custom_view, array('assets_dir' => $view->assets_dir, 'entities' => $entities));
		$this->html .= "\n" . $this->paginate();
		$this->html .= ($this->total > 0)? 'Total items: <strong>' . $this->total . "</strong>\n" : 'No results found';
		$this->html .= '		</div>' . "\n";

		return $this->html;
	}

}
