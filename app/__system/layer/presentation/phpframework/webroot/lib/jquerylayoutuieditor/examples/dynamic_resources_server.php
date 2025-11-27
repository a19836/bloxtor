<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original JQuery Layout UI Editor Repo: https://github.com/a19836/jquerylayoutuieditor/
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

session_start();

$resource = @$_GET["resource"];
$result = null;

if (isset($_SESSION['items'])) {
	$items = $_SESSION['items'];

	//just in case, be sure $items is always an array
	if (!is_array($items))
		$items = array();
}
else
	$items = array(
		array("item_id" => "1", "name" => "Item A"),
		array("item_id" => "2", "name" => "Item B"),
		array("item_id" => "3", "name" => "Item C")
	);

switch ($resource) {
	case "items":
		$items_limit_per_page = @$_GET["items_limit_per_page"];
		$page_items_start = @$_GET["page_items_start"];
		$page_number = @$_GET["page_number"];
		$search_attrs = @$_GET["search_attrs"];
		$search_types = @$_GET["search_types"];
		$search_cases = @$_GET["search_cases"];
		$search_operators = @$_GET["search_operators"];
		$sort_attrs = @$_GET["sort_attrs"];
		
		//get items
		if ($search_attrs && is_array($search_attrs)) { //filter result if $search_attrs
			$search_types = is_array($search_types) ? $search_types : array();
			$search_cases = is_array($search_cases) ? $search_cases : array();
			$search_operators = is_array($search_operators) ? $search_operators : array();
			
			$result = filterItems($items, $search_attrs, $search_types, $search_cases, $search_operators);
		}
		else
			$result = $items;
		
		//sort items if $sort_attrs
		if ($result && $sort_attrs)
			$result = sortByAttributes($result, $sort_attrs);
		
		//paginate items
		if ($result && $items_limit_per_page) {
			$pagination = paginateItems($result, $items_limit_per_page, $page_items_start, $page_number);
			$result = $pagination["items"];
		}
		
		break;
		
	case "count_items":
		$search_attrs = @$_GET["search_attrs"];
		$search_types = @$_GET["search_types"];
		$search_cases = @$_GET["search_cases"];
		$search_operators = @$_GET["search_operators"];
		
		//get items
		if ($search_attrs && is_array($search_attrs)) { //filter result if $search_attrs
			$search_types = is_array($search_types) ? $search_types : array();
			$search_cases = is_array($search_cases) ? $search_cases : array();
			$search_operators = is_array($search_operators) ? $search_operators : array();
			
			$result = filterItems($items, $search_attrs, $search_types, $search_cases, $search_operators);
		}
		else
			$result = $items;
			
		//count items
		$result = count($result);
		break;
		
	case "item":
		$search_attrs = @$_GET["search_attrs"];
		$item_id = @$search_attrs["item_id"];
		
		if ($item_id)
			for ($i = 0, $t = count($items); $i < $t; $i++)
				if ($items[$i]["item_id"] == $item_id) {
					$result = $items[$i];
					break 1;
				}
			
		break;
		
	case "delete_all_items":
		$conditions = @$_POST["conditions"];
		
		if ($conditions)
			for ($i = 0, $ti = count($conditions); $i < $ti; $i++) {
				$condition = $conditions[$i];
				$item_id = $condition["item_id"];
				
				if ($item_id)
					for ($j = 0, $tj = count($items); $j < $tj; $j++)
						if ($items[$j]["item_id"] == $item_id) {
							$result = 1;
							
							unset($items[$j]);
							$items = array_values($items);
							
							break 1;
						}
			}
		
		break;
		
	case "delete_item":
		$conditions = @$_POST["conditions"];
		$item_id = @$conditions["item_id"];
		
		if ($item_id)
			for ($i = 0, $t = count($items); $i < $t; $i++)
				if ($items[$i]["item_id"] == $item_id) {
					$result = 1;
					
					unset($items[$i]);
					$items = array_values($items);
					
					break 1;
				}
				
		break;
		
	case "insert_item":
		$attributes = @$_POST["attributes"];
		
		if ($attributes && is_array($attributes)) {
			$result = 1;
			
			$item = $attributes;
			$item_id = 1;
			
			for ($i = count($items) - 1; $i >= 0; $i--)
				if ($items[$i]["item_id"] >= $item_id)
					$item_id = $items[$i]["item_id"] + 1;
			
			$item["item_id"] = $item_id;
			
			$items[] = $item;
		}
		break;
		
	case "update_item":
		$attributes = @$_POST["attributes"];
		$conditions = @$_POST["conditions"];
		$item_id = @$conditions["item_id"];
		
		if ($item_id && $attributes && is_array($attributes))
			for ($i = 0, $t = count($items); $i < $t; $i++)
				if ($items[$i]["item_id"] == $item_id) {
					$result = 1;
					
					//$items[$i]["name"] = $attributes["name"];
					foreach ($attributes as $k => $v)
						$items[$i][$k] = $v;
					
					break 1;
				}
		
		break;
		
	case "update_item_attribute":
		$attributes = @$_POST["attributes"];
		$conditions = @$_POST["conditions"];
		$item_id = @$conditions["item_id"];
		
		if ($item_id && $attributes && is_array($attributes)) {
			$key = key($attributes);
			$value = $attributes[$key]; //$value = $attributes["name"];
			
			for ($i = 0, $t = count($items); $i < $t; $i++)
				if ($items[$i]["item_id"] == $item_id) {
					$result = 1;
					
					//if (isset($items[$i][$key]))
						$items[$i][$key] = $value;
					
					break 1;
				}
		}
		
		break;
}

$_SESSION['items'] = $items;

$json = json_encode($result);
echo $json;

function sortByAttributes(array $items, array $sort_attrs) {
    usort($items, function ($a, $b) use ($sort_attrs) {
        foreach ($sort_attrs as $field => $direction) {
            // Normalize direction
            $direction = strtolower($direction) === "desc" ? -1 : 1;

            // Compare values
            if ($a[$field] < $b[$field]) return -1 * $direction;
            if ($a[$field] > $b[$field]) return 1 * $direction;
            // If equal, try next sort attribute
        }

        return 0; // completely equal
    });

    return $items;
}

function filterItems(array $items, array $search_attrs, array $search_types, array $search_cases, array $search_operators) {
    return array_filter($items, function ($item) use ($search_attrs, $search_types, $search_cases, $search_operators) {
        $results = [];

        foreach ($search_attrs as $field => $search_value) {
            // Skip empty search values (means no filter for this field)
            if ($search_value === "" || $search_value === null)
                continue;

            if (!isset($item[$field])) {
                $results[$field] = false;
                continue;
            }

            $item_value = (string)$item[$field];
            $type = $search_types[$field] ?? "contains";
            $case = strtolower($search_cases[$field] ?? "");
            $operator = strtolower($search_operators[$field] ?? "or");

            // Case handling
            if ($case !== "sensitive") {
                $item_value = mb_strtolower($item_value);
                $search_value = mb_strtolower($search_value);
            }

            // Comparison logic
            switch ($type) {
                case "starts_with":
                    $match = str_starts_with($item_value, $search_value);
                    break;

                case "ends_with":
                    $match = str_ends_with($item_value, $search_value);
                    break;

                case "equal":
                    $match = ($item_value === $search_value);
                    break;

                case "contains":
                default:
                    $match = (strpos($item_value, $search_value) !== false);
                    break;
            }

            $results[$field] = $match;
        }

        if (empty($results))
            return true; // nothing to filter

        // Decide global AND/OR operator
        // If ANY field is OR → treat OR
        $use_and = !in_array("or", array_map('strtolower', $search_operators), true);

        if ($use_and) // All conditions must be true
            return !in_array(false, $results, true);
        else // At least one condition must be true
            return in_array(true, $results, true);
    });
}

function paginateItems(array $items, $items_limit_per_page, $page_items_start, $page_number) {
    // Normalize values
    $limit = is_numeric($items_limit_per_page) && $items_limit_per_page > 0 
                ? (int)$items_limit_per_page 
                : count($items);

    // Determine start offset
    if (is_numeric($page_items_start) && $page_items_start >= 0)
        $start = (int)$page_items_start;
    elseif (is_numeric($page_number) && $page_number >= 1)
        $start = ((int)$page_number - 1) * $limit;
    else
        $start = 0;

    // Slice the list
    $paged = array_slice($items, $start, $limit);

    // Return with info
    return [
        "items"        => $paged,
        "total_items"  => count($items),
        "limit"        => $limit,
        "start"        => $start,
        "page_number"  => isset($page_number) ? (int)$page_number : 1,
        "pages_total"  => $limit > 0 ? ceil(count($items) / $limit) : 1
    ];
}
?>
