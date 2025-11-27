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

include_once get_lib("org.phpframework.util.io.handler.awss3.MyS3Handler");

class MyS3Bucket extends MyS3Handler {
	public function __construct($awsAccessKey, $awsSecretKey) {
		parent::__construct($awsAccessKey, $awsSecretKey);
	}
	
	/*
	 * create: creates new bucket
	 */
	public function create($bucket, $perm = "p", $location = false) {
		$acl = $this->getACL($perm);
		return $this->S3->putBucket($bucket, $acl, $location);
	}
	
	/*
	 * delete: deletes bucket
	 */
	public function delete($bucket) {
		$status = true;
		
		$files = $this->getBucketFiles($bucket);
		foreach($files as $key => $value) {
			if(!$this->S3->deleteObject($bucket, $key))
				$status = false;
		}
		
		return $status ? $this->S3->deleteBucket($bucket) : false;
	}
	
	/*
	 * getBucketFiles: gets bucket files
	 */
	public function getBucketFiles($bucket) {
		return $this->S3->getBucket($bucket);
	}
	
	/*
	 * getLocation: gets bucket location
	 */
	public function getLocation($bucket) {
		return $this->S3->getBucketLocation($bucket);
	}
	
	/*
	 * getList: gets buckets list
	 */
	public function getList($detailed = true) {
		return $this->S3->listBuckets($detailed);
	}
}
?>
