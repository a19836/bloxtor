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

interface IHibernateClientCacheLayer {
    // Check if file exists and if TTL is still valid
    public function isValid($module_id, $service_id, $parameters = false, $options = false);

    // Get cached result
    public function get($module_id, $service_id, $parameters = false, $options = false);

    // Save result in cache
    public function check($module_id, $service_id, $parameters = false, $result = false, $options = false);
}
?>
