<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

include_once get_lib("org.phpframework.cache.CacheHandlerUtil");

class TextTranslator {
	private $root_folder_path;
	private $default_lang;
	
	private $loaded_langs;
	private $category_categories;
	private $category_languages;
	
	public function __construct($root_folder_path, $default_lang) {
		$this->root_folder_path = $root_folder_path . "/";
		$this->default_lang = $default_lang;
		
		//error_log("\n[$root_folder_path] => ".$default_lang, 3, "/tmp/test.log");
		$this->loaded_langs = array();
		$this->category_categories = array();
		$this->category_languages = array();
	}
	
	public function getDefaultLang() {
		return $this->default_lang;
	}
	
	/* TRANSLATIONS METHODS */
	
	private function getTranslationsFolderPath($category = null) {
		return $this->root_folder_path . ($category ? "$category/" : "");
	}
	
	private function getTranslationsFilePath($category = null, $lang = null) {
		return $this->getTranslationsFolderPath($category) . ($lang ? $lang : $this->default_lang) . ".php";
	}
	
	private function loadTranslationsFile($file_path) {
		if (!isset($this->loaded_langs[$file_path])) {
			if (file_exists($file_path)) {
				$contents = file_get_contents($file_path);
				$tt = $contents ? unserialize($contents) : null;
				$this->loaded_langs[$file_path] = is_array($tt) ? $tt : array();
			}
			else
				$this->loaded_langs[$file_path] = array();
		}
	}
	
	public function setTranslationsFile($translations, $category = null, $lang = null) {
		$file_path = $this->getTranslationsFilePath($category, $lang);
		return file_put_contents($file_path, serialize($translations)) !== false;
	}
	
	public function replaceTextTranslationInFile($text, $translation, $category = null, $lang = null) {
		$this->replaceTextTranslation($text, $translation, $category, $lang);
		$file_path = $this->getTranslationsFilePath($category, $lang);
		
		$loaded_langs = isset($this->loaded_langs[$file_path]) ? $this->loaded_langs[$file_path] : null;
		
		return file_put_contents($file_path, serialize($loaded_langs)) !== false;
	}
	
	public function replaceTextTranslation($text, $translation, $category = null, $lang = null) {
		$file_path = $this->getTranslationsFilePath($category, $lang);
		$this->loadTranslationsFile($file_path);
		
		$this->loaded_langs[$file_path][$text] = $translation;
		
		return isset($this->loaded_langs[$file_path]) ? $this->loaded_langs[$file_path] : null;
	}
	
	public function removeTextTranslationInFile($text, $category = null, $lang = null) {
		$this->removeTextTranslation($text, $category, $lang);
		$file_path = $this->getTranslationsFilePath($category, $lang);
		
		$loaded_langs = isset($this->loaded_langs[$file_path]) ? $this->loaded_langs[$file_path] : null;
		
		return file_put_contents($file_path, serialize($loaded_langs)) !== false;
	}
	
	public function removeTextTranslation($text, $category = null, $lang = null) {
		$file_path = $this->getTranslationsFilePath($category, $lang);
		$this->loadTranslationsFile($file_path);
		
		unset($this->loaded_langs[$file_path][$text]);
		
		return isset($this->loaded_langs[$file_path]) ? $this->loaded_langs[$file_path] : null;
	}
	
	public function addTextTranslationsFile($file_path, $category = null, $lang = null, $only_new = false) {
		if (file_exists($file_path)) {
			$contents = file_get_contents($file_path);
			$text_translations = $contents ? unserialize($contents) : null;
			
			return $this->addTextTranslations($text_translations, $category, $lang, $only_new);
		}
	}
	
	public function addTextTranslations($text_translations, $category = null, $lang = null, $only_new = false) {
		if ($text_translations) {
			$file_path = $this->getTranslationsFilePath($category, $lang);
			$this->loadTranslationsFile($file_path);
			
			if (is_array($text_translations))
				foreach ($text_translations as $text => $translation)
					if (!$only_new || !isset($this->loaded_langs[$file_path][$text]))
						$this->loaded_langs[$file_path][$text] = $translation;
			
			return isset($this->loaded_langs[$file_path]) ? $this->loaded_langs[$file_path] : null;
		}
	}
	
	public function getTextTranslation($text, $category = null, $lang = null) {
		$file_path = $this->getTranslationsFilePath($category, $lang);
		$this->loadTranslationsFile($file_path);
		
		//error_log("\n$file_path", 3, "/tmp/test.log");
		//error_log("\n[$text][$category][$lang] => ".$this->loaded_langs[$file_path][$text], 3, "/tmp/test.log");
		return isset($this->loaded_langs[$file_path][$text]) ? $this->loaded_langs[$file_path][$text] : null;
	}
	
	public function translateText($text, $category = null, $lang = null) {
		$translation = $this->getTextTranslation($text, $category, $lang);
		return $translation ? $translation : $text;
	}
	
	public function translateTextFromFile($file_path, $text) {
		$this->loadTranslationsFile($file_path);
		return isset($this->loaded_langs[$file_path][$text]) ? $this->loaded_langs[$file_path][$text] : null;
	}
	
	public function getTranslations($category = null, $lang = null) {
		$file_path = $this->getTranslationsFilePath($category, $lang);
		$this->loadTranslationsFile($file_path);
		
		return isset($this->loaded_langs[$file_path]) ? $this->loaded_langs[$file_path] : null;
	}
	
	/* CATEGORIES METHODS */
	
	public function insertCategory($category) {
		return $category && (is_dir($this->root_folder_path . $category) || mkdir($this->root_folder_path . $category, 0755, true));
	}
	
	public function updateCategory($old_category, $new_category) {
		if ($old_category && $new_category && is_dir($this->root_folder_path . $old_category))
			return rename($this->root_folder_path . $old_category, $this->root_folder_path . $new_category);
	}
	
	public function removeCategory($category) {
		if ($category && is_dir($this->root_folder_path . $category))
			return CacheHandlerUtil::deleteFolder($this->root_folder_path . $category);
	}
	
	public function categoryExists($category) {
		return $category && is_dir($this->root_folder_path . $category);
	}
	
	public function getCategories($category = null, $lang = null, $recursive = false) {
		$id = md5($category . "_" . $lang . "_" . $recursive);
		
		if (isset($this->category_categories[$id]))
			return $this->category_categories[$id];
		
		$categories = array();

		$path = $category ? "$category/" : "";
		$files = array_diff(scandir($this->root_folder_path . $path), array('.', '..'));
	
		foreach ($files as $file) {
			if ($recursive && is_dir($this->root_folder_path. $path . $file)) {
				if (!$lang)
					$categories[] = $path . $file;
				
				$categories = array_merge($categories, $this->getCategories($path . $file, $lang, $recursive));
			}
			else if (!$lang || $file == $lang . ".php")
				$categories[] = $path ? dirname($path . $file) : "";
		}
		
		$categories = array_unique($categories);
		$this->category_categories[$id] = $categories;
	
		return $categories;
	}
	
	public function getLanguageCategories($lang = null, $recursive = false) {
		return $this->getCategories(null, $lang, $recursive);
	}
	
	public function getTextCategories($text, $category = null, $lang = null, $recursive = false) {
		$text_categories = array();
	
		$categories = $this->getCategories($category, $lang, $recursive);
		
		foreach ($categories as $category) {
			$available_languages = $lang ? array($lang) : $this->getCategoryLanguages($category);
			
			foreach ($available_languages as $lang) {
				$langs = $this->getTranslations($category, $lang);
				
				if (isset($langs[$text]))
					$text_categories[] = $category;
			}
		}
		
		return $text_categories;
	}
	
	/* LANGUAGES METHODS */
	
	public function insertLanguage($lang, $category = null) {
		return $lang && (file_exists($this->root_folder_path . "$category/{$lang}.php") || file_put_contents($this->root_folder_path . "$category/{$lang}.php", "") !== false);
	}
	
	public function updateLanguage($old_lang, $new_lang, $category = null, $recursive = false) {
		if ($old_lang && $new_lang) {
			$categories = $this->getCategories($category, $old_lang, $recursive);
			
			if ($categories) {
				$status = true;
			
				foreach ($categories as $category)
					if (file_exists($this->root_folder_path . "$category/{$old_lang}.php") && !rename($this->root_folder_path . "$category/{$old_lang}.php", $this->root_folder_path . "$category/{$new_lang}.php"))
						$status = false;
		
				return $status;
			}
		}
	}
	
	public function removeLanguage($lang, $category = null, $recursive = false) {
		if ($lang) {
			$categories = $this->getCategories($category, $lang, $recursive);
			$status = true;
		
			foreach ($categories as $category)
				if (file_exists($this->root_folder_path . "$category/{$lang}.php") && !unlink($this->root_folder_path . "$category/{$lang}.php"))
					$status = false;
	
			return $status;
		}
	}
	
	public function languageExists($lang, $category = null) {
		return $lang && file_exists($this->root_folder_path . "$category/{$lang}.php");
	}
	
	public function getCategoryLanguages($category = null, $recursive = false) {
		$id = md5($category . "_" . $recursive);
		
		if (isset($this->category_languages[$id]))
			return $this->category_languages[$id];
		
		$languages = array();
	
		$path = $category ? "$category/" : "";
		$files = array_diff(scandir($this->root_folder_path . $path), array('.', '..'));
	
		foreach ($files as $file) {
			if ($recursive && is_dir($this->root_folder_path . $path . $file))
				$languages = array_merge($languages, $this->getCategoryLanguages($path . $file, $recursive));
			else if (substr($file, -4) == ".php")
				$languages[] = pathinfo($file, PATHINFO_FILENAME);
		}
	
		$languages = array_unique($languages);
		$this->category_languages[$id] = $languages;
		
		return $languages;
	}
	
	public function getTextLanguages($text, $category = null, $lang = null, $recursive = false) {
		$text_languages = array();
	
		$categories = $this->getCategories($category, $lang, $recursive);
		
		foreach ($categories as $category) {
			$available_languages = $lang ? array($lang) : $this->getCategoryLanguages($category);
			
			foreach ($available_languages as $lang) {
				$langs = $this->getTranslations($category, $lang);
				
				if (isset($langs[$text]))
					$text_languages[] = $lang;
			}
		}
		
		return array_unique($text_languages);
	}
}
?>
