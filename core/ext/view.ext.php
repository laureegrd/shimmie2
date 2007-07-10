<?php

class ViewImage extends Extension {
// event handling {{{
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent') && ($event->page == "post") && ($event->get_arg(0) == "view")) {
			$image_id = int_escape($event->get_arg(1));
			
			global $database;
			$image = $database->get_image($image_id);

			if(!is_null($image)) {
				send_event(new DisplayingImageEvent($image, $event->page));
			}
			else {
				global $page;
				$page->set_title("Image not found");
				$page->set_heading("Image not found");
				$page->add_block(new NavBlock());
				$page->add_block(new Block("Image not found",
					"No image in the database has the ID #$image_id"));
			}
		}

		if(is_a($event, 'DisplayingImageEvent')) {
			$image = $event->get_image();
			
			global $page;
			$page->set_title("Image {$image->id}: ".html_escape($image->get_tag_list()));
			$page->set_heading(html_escape($image->get_tag_list()));
			$page->add_block(new Block("Navigation", $this->build_navigation($image->id), "left", 0));
			$page->add_block(new Block("Image", $this->build_image_view($image), "main", 0));
			$page->add_block(new Block(null, $this->build_info($image), "main", 10));
		}

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("View Options");
			$sb->position = 30;
			$sb->add_text_option("image_ilink", "Long link ");
			$sb->add_text_option("image_slink", "<br>Short link ");
			$sb->add_text_option("image_tlink", "<br>Thumbnail link ");
			$event->panel->add_block($sb);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$event->config->set_string_from_post("image_ilink");
			$event->config->set_string_from_post("image_slink");
			$event->config->set_string_from_post("image_tlink");
		}
	}
// }}}
// HTML {{{
	var $pin = null;

	private function build_pin($image_id) {
		if(!is_null($this->pin)) {
			return $this->pin;
		}

		global $database;

		// $next_img = $database->db->GetOne("SELECT id FROM images WHERE id < ? ORDER BY id DESC", array($image_id));
		// $prev_img = $database->db->GetOne("SELECT id FROM images WHERE id > ? ORDER BY id ASC ", array($image_id));
		if(isset($_GET['search'])) {
			$search_terms = explode(' ', $_GET['search']);
			$query = "search=".url_escape($_GET['search']);
		}
		else {
			$search_terms = array();
			$query = null;
		}
		
		$next = $database->get_next_image($image_id, $search_terms);
		$prev = $database->get_prev_image($image_id, $search_terms);

		$h_prev = (!is_null($prev) ? "<a href='".make_link("post/view/{$prev->id}", $query)."'>Prev</a>" : "Prev");
		$h_index = "<a href='".make_link("index")."'>Index</a>";
		$h_next = (!is_null($next) ? "<a href='".make_link("post/view/{$next->id}", $query)."'>Next</a>" : "Next");

		$this->pin = "$h_prev | $h_index | $h_next";
		return $this->pin;
	}

	private function build_navigation($image_id) {
		$h_pin = $this->build_pin($image_id);
		$h_search = "
			<p><form action='".make_link("index")."' method='GET'>
				<input id='search_input' name='search' type='text'
						value='Search' autocomplete='off'>
				<input type='submit' value='Find' style='display: none;'>
			</form>
			<div id='search_completions'></div>";

		return "$h_pin<br>$h_search";
	}

	private function build_image_view($image) {
		$ilink = $image->get_image_link();
		return "<img id='main_image' src='$ilink'>";
	}

	private function build_info($image) {
		global $user;
		$owner = $image->get_owner();
		$h_owner = html_escape($owner->name);
		$h_ip = html_escape($image->owner_ip);
		$i_owner_id = int_escape($owner->id);

		$html = "";
		if(strlen($image->get_short_link()) > 0) {
			$slink = $image->get_short_link();
			$html .= "<p>Link: <input size='50' type='text' value='$slink'>";
		}
		$html .= "<p>Uploaded by <a href='".make_link("user/$h_owner")."'>$h_owner</a>";
		if($user->is_admin()) {
			$html .= " ($h_ip)";
		}
		$html .= "<p>".$this->build_pin($image->id);
		
		return $html;
	}
// }}}
}
add_event_listener(new ViewImage());
?>
