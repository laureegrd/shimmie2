<?php

// Tagger AJAX back-end
class TaggerXML extends Extension
{
    public function get_priority(): int
    {
        return 10;
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        if ($event->page_matches("tagger/tags")) {
            global $page;

            //$match_tags = null;
            //$image_tags = null;
            $tags=null;
            if (isset($_GET['s'])) { // tagger/tags[/...]?s=$string
                // return matching tags in XML form
                $tags = $this->match_tag_list($_GET['s']);
            } elseif ($event->get_arg(0)) { // tagger/tags/$int
                // return arg[1] AS image_id's tag list in XML form
                $tags = $this->image_tag_list($event->get_arg(0));
            }

            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
            "<tags>".
                $tags.
            "</tags>";

            $page->set_mode(PageMode::DATA);
            $page->set_type("text/xml");
            $page->set_data($xml);
        }
    }

    private function match_tag_list(string $s)
    {
        global $database, $config;

        $max_rows = $config->get_int("ext_tagger_tag_max", 30);
        $limit_rows = $config->get_int("ext_tagger_limit", 30);

        $values = [];

        // Match
        $p = strlen($s) == 1? " ":"\_";
        $sq = "%".$p.sql_escape($s)."%";
        $match = "concat(?,tag) LIKE ?";
        array_push($values, $p, $sq);
        // Exclude
        //		$exclude = $event->get_arg(1)? "AND NOT IN ".$this->image_tags($event->get_arg(1)) : null;

        // Hidden Tags
        $hidden = $config->get_string('ext-tagger_show-hidden', 'N')=='N' ?
            "AND substring(tag,1,1) != '.'" : null;

        $q_where = "WHERE {$match} {$hidden} AND count > 0";

        // FROM based on return count
        $count = $this->count($q_where, $values);
        if ($count > $max_rows) {
            $q_from = "FROM (SELECT * FROM `tags` {$q_where} ".
                "ORDER BY count DESC LIMIT 0, {$limit_rows}) AS `c_tags`";
            $q_where = null;
            $count = ["max"=>$count];
        } else {
            $q_from = "FROM `tags`";
            $count = null;
        }

        $tags = $database->Execute(
            "
			SELECT *
			{$q_from}
			{$q_where}
			ORDER BY tag",
            $values
        );

        return $this->list_to_xml($tags, "search", $s, $count);
    }

    private function image_tag_list(int $image_id)
    {
        global $database;
        $tags = $database->Execute("
			SELECT tags.*
			FROM image_tags JOIN tags ON image_tags.tag_id = tags.id
			WHERE image_id=? ORDER BY tag", [$image_id]);
        return $this->list_to_xml($tags, "image", $image_id);
    }

    private function list_to_xml(PDOStatement $tags, string $type, string $query, ?array$misc=null): string
    {
        $r = $tags->_numOfRows;

        $s_misc = "";
        if (!is_null($misc)) {
            foreach ($misc as $attr => $val) {
                $s_misc .= " ".$attr."=\"".$val."\"";
            }
        }

        $result = "<list id=\"$type\" query=\"$query\" rows=\"$r\"{$s_misc}>";
        foreach ($tags as $tag) {
            $result .= $this->tag_to_xml($tag);
        }
        return $result."</list>";
    }

    private function tag_to_xml(PDORow $tag): string
    {
        return
            "<tag  ".
                "id=\"".$tag['id']."\" ".
                "count=\"".$tag['count']."\">".
                html_escape($tag['tag']).
                "</tag>";
    }

    private function count(string $query, $values)
    {
        global $database;
        return $database->Execute(
            "SELECT COUNT(*) FROM `tags` $query",
            $values
        )->fields['COUNT(*)'];
    }
}
