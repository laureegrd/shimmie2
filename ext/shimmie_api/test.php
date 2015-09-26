<?php
class ShimmieApiTest extends ShimmieWebTestCase {
	public function testAPI() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx");

		$this->get_page("api/shimmie/get_tags");
		$this->get_page("api/shimmie/get_tags/pb");
		$this->get_page("api/shimmie/get_tags?tag=pb");
		$this->get_page("api/shimmie/get_image/$image_id");
		$this->get_page("api/shimmie/get_image?id=$image_id");
		$this->get_page("api/shimmie/find_images");
		$this->get_page("api/shimmie/find_images/pbx");
		$this->get_page("api/shimmie/find_images/pbx/1");
		$this->get_page("api/shimmie/get_user/demo");
		$this->get_page("api/shimmie/get_user?name=demo");
		$this->get_page("api/shimmie/get_user?id=2");

		// FIXME: test unspecified / bad values
		// FIXME: test that json is encoded properly
		
		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}
}

