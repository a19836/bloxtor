<?php
include_once get_lib("org.phpframework.util.web.MyCurl");
include_once get_lib("lib.vendor.bpetokeniser.autoload");

/*
 * ## TOKENS LIMITS
 * 
 * The **4096 token limit** in GPT-3.5-turbo refers to the total number of tokens for **both input and output combined**. Here's a breakdown of what that means:
 * 
 * ### What Are Tokens?
 * Tokens are chunks of text that can be as short as one character or as long as one word. For example:
 * - A single word like "hello" is typically 1 token.
 * - A complex word or part of a sentence like "multiplied-by" may count as multiple tokens.
 * - A space, punctuation, or newline also counts as a token.
 * 
 * You can estimate tokens as follows:
 * - 1 token ≈ 4 characters in English.
 * - 100 tokens ≈ 75 words on average.
 * 
 * ### Input + Output
 * The token limit accounts for:
 * 1. **Input Tokens**: Everything you send to OpenAI in the request, which includes:
 *    - The `system` role message (if used).
 *    - All previous `assistant` and `user` messages (if you're maintaining a conversation).
 *    - The current `user` message you send.
 * 
 * 2. **Output Tokens**: Everything OpenAI sends back in its response.
 * 
 * The combined total of these tokens must not exceed 4096 tokens for GPT-3.5-turbo.
 * 
 * ---
 * 
 * ### Example Calculation
 * 
 * #### Input Example:
 * ```json
 * {
 *   "messages": [
 *     { "role": "system", "content": "You are a helpful assistant." },
 *     { "role": "user", "content": "Explain the PHP `foreach` loop in detail." }
 *   ]
 * }
 * ```
 * - The `system` message might use 6 tokens (`You are a helpful assistant.`).
 * - The `user` message might use 9 tokens (`Explain the PHP foreach loop in detail.`).
 * - Total input tokens: **15 tokens**.
 * 
 * #### Output Example:
 * If OpenAI replies with:
 * ```plaintext
 * The `foreach` loop in PHP is used to iterate over arrays. It simplifies the process of traversing elements...
 * ```
 * - The response might use 60 tokens.
 * 
 * #### Total Tokens:
 * Input tokens (15) + Output tokens (60) = **75 tokens** (well within the 4096 limit).
 * 
 * ---
 * 
 * ### Key Considerations
 * 1. **Long Conversations**: If you're maintaining context by including previous messages, the input tokens can grow quickly.
 * 2. **Response Length**: If you request a long response, the output tokens increase.
 * 3. **Handling the Limit**: If the total input + output exceeds 4096 tokens, OpenAI will truncate the input or fail the request.
 * 
 * ---
 * 
 * ### How to Manage the Token Limit
 * - **Efficient Prompts**: Write concise instructions to reduce input tokens.
 * - **Summarize History**: In long conversations, summarize prior exchanges instead of including all messages.
 * - **Control Output**: Use parameters like `max_tokens` in your API call to limit the output size. For instance:
 *   ```json
 *   { "max_tokens": 200 }
 *   ```
 * - **Chunk Inputs**: For large files or tasks, split them into smaller chunks that stay within the token limit.
 * 
 * The max_tokens parameter in OpenAI's API exclusively controls the maximum number of tokens in the model's output response. It does not affect the input tokens you send as part of your request.
 * 
 * ### Key Points About `max_tokens`:
 * 1. **Output Limitation Only**: It restricts how long the model's reply can be.
 *    - For example, setting `max_tokens: 200` means the model will generate at most 200 tokens in its response, even if it has more to say.
 *    
 * 2. **Total Token Limit**: The combined total of input tokens and the maximum output tokens (as specified by `max_tokens`) must not exceed the model's token limit:
 *    - For GPT-3.5-turbo: **4096 tokens**.
 *    - For GPT-4 (8k variant): **8192 tokens**.
 * 
 * 3. **Truncation**: If the output exceeds the specified `max_tokens`, the model's response will be truncated.
 * 
 * ---
 * 
 * ### Example:
 * Suppose you send a request with:
 * - Input tokens: 300 tokens.
 * - `max_tokens`: 200.
 * 
 * The model will:
 * - Process your 300 input tokens.
 * - Reserve up to 200 tokens for its reply.
 * - The total tokens (300 + 200 = 500) will be well within GPT-3.5-turbo's 4096 token limit.
 * 
 * ---
 * 
 * ### Why Use `max_tokens`?
 * - **Prevent Overly Long Replies**: Limit responses to a manageable length.
 * - **Optimize Costs**: Since OpenAI charges by token usage, controlling output length can reduce costs.
 * - **Avoid Exceeding Token Limit**: Reserving space for a reply ensures the model doesn’t run out of capacity for a response.
 * 
*/
class OpenAIHandler {
	
	private $openai_api_key; //openai secret key
	private $default_url; //openai url: https://api.openai.com/v1/chat/completions or https://api.openai.com/v1/images/generations
	private $default_model; //openai model: gpt-3.5-turbo, gpt-4...
	private $default_max_tokens; //openai reply output maximum tokens.
	private $default_temperature; //openai processing temperature.
	
	private $EncodingFactory;
	private $errors;
	
	private static $available_models_tokens_limits = array(
		"gpt-3.5-turbo" => 4096, //tokens max (input + output). gpt-3.5-turbo is faster than gpt-4
		"gpt-4" => 8192, //tokens max (or more for some configurations).
		"gpt-4o" => 16384, //tokens max (or more for some configurations).
	);
	
	public function __construct($openai_api_key, $url = null, $model = null, $max_tokens = null, $temperature = null) {
		$this->openai_api_key = $openai_api_key;
		$this->default_url = $url ? $url : "https://api.openai.com/v1/chat/completions";
		$this->default_model = $model ? strtolower($model) : "gpt-3.5-turbo";
		$this->default_max_tokens = $max_tokens > 0 ? $max_tokens : 150;
		$this->default_temperature = $temperature > 0 ? $temperature : 0.7;
		
		$this->initEncodingFactory();
		$this->resetErrors();
	}
	
	public function addError($error) {
		$this->errors[] = $error;
	}
	public function getErrors() {
		return $this->errors;
	}
	public function resetErrors() {
		$this->errors = array();
	}
	
	public function initEncodingFactory($mode = null) {
		$this->EncodingFactory = Danny50610\BpeTokeniser\EncodingFactory::createByModelName($model ? strtolower($model) : $this->default_model);
	}
	
	public function generateImage($user_content, $number = null, $size = null, $quality = null, $options = array()) {
		//send multiple messages
		$url = !empty($options["url"]) ? $options["url"] : $this->default_url;
		$model = !empty($options["model"]) ? strtolower($options["model"]) : $this->default_model;
		
		//prepare curl multiple data
		$curl_multiple_data = array();
		
		//prepare post data
		$post_data = array(
			"model" => $model,
			"prompt" => $user_content,
			"n" => 1, //must be always 1
			//"size" => $size ? $size : "1024x1024",
			//"quality" => $quality ? $quality : "standard"
		);
		
		if ($size)
			$post_data["size"] = $size; // Size of the image: 1024x1024
		
		if ($quality)
			$post_data["quality"] = $quality; // Quality of the image: standard
		
		//prepare curl item data
		$number = $number > 0 ? $number : 1;
		
		for ($i = 0; $i < $number; $i++)
			$curl_multiple_data[] = array(
				"url" => $url,
				"post" => json_encode($post_data),
				"settings" => array(
					"http_header" => array(
						"Content-Type: application/json",
						"Authorization: Bearer " . $this->openai_api_key
					),
					"do_not_prepare_post_data" => true
				)
			);
		//error_log("curl_multiple_data:" . print_r($curl_multiple_data, 1) . "\n\n", 3, $GLOBALS["log_file_path"]);
		
		//send request
		$items = array();
		
		if (count($curl_multiple_data) > 1) {
			$MyCurl = new MyCurl();
			$MyCurl->initMultiple($curl_multiple_data);
			$MyCurl->get_contents(array("wait" => true));
			$request = $MyCurl->getData();
			//echo "<pre>";print_r($request);die();
		
			//prepare response output
			$t = $request ? count($request) : 0;
			for ($i = 0; $i < $t; $i++) {
				//error_log("request[$i]:" . print_r($request[$i], 1) . "\n\n", 3, $GLOBALS["log_file_path"]);
				
				$content = isset($request[$i]["content"]) ? $request[$i]["content"] : null;
				$response = json_decode($content, true);
				
				$items = array_merge($items, $this->getResponseImagesUrl($response));
			}
		}
		else if ($curl_multiple_data) {
			$response = MyCurl::getUrlContents($curl_multiple_data[0], "content_json");
			//error_log("response:" . print_r($response, 1) . "\n\n", 3, $GLOBALS["log_file_path"]);
			$items = $this->getResponseImagesUrl($response);
		}
		
		return $items;
	}
	
	public function generateMessage($system_content, $user_content, $previous_messages = null, $options = array()) {
		//send multiple messages
		$url = !empty($options["url"]) ? $options["url"] : $this->default_url;
		$model = !empty($options["model"]) ? strtolower($options["model"]) : $this->default_model;
		$temperature = !empty($options["temperature"]) && is_numeric($options["temperature"]) ? $options["temperature"] : $this->default_temperature;
		$max_tokens = !empty($options["max_tokens"]) && is_numeric($options["max_tokens"]) ? $options["max_tokens"] : null;
		
		//prepare EncodingFactory
		if ($model != $this->default_model)
			$this->initEncodingFactory($model);
		
		//prepare messages
		$group_messages = $this->groupMessagesByChunks($system_content, $user_content, $previous_messages, $options);
		
		//prepare curl multiple data
		$curl_multiple_data = array();
		
		foreach ($group_messages as $messages) {
			//prepare post data
			$post_data = $this->getPostData($model, $messages, $max_tokens, $temperature);
			
			//prepare curl item data
			$curl_multiple_data[] = array(
				"url" => $url,
				"post" => json_encode($post_data),
				"settings" => array(
					"http_header" => array(
						"Content-Type: application/json",
						"Authorization: Bearer " . $this->openai_api_key
					),
					"do_not_prepare_post_data" => true
				)
			);
		}
		//error_log("curl_multiple_data:" . print_r($curl_multiple_data, 1) . "\n\n", 3, $GLOBALS["log_file_path"]);
		
		//send request
		$reply = "";
		
		if (count($curl_multiple_data) > 1) {
			$MyCurl = new MyCurl();
			$MyCurl->initMultiple($curl_multiple_data);
			$MyCurl->get_contents(array("wait" => true));
			$request = $MyCurl->getData();
			//echo "<pre>";print_r($request);die();
		
			//prepare response output
			$t = $request ? count($request) : 0;
			for ($i = 0; $i < $t; $i++) {
				//error_log("request[$i]:" . print_r($request[$i], 1) . "\n\n", 3, $GLOBALS["log_file_path"]);
				
				$content = isset($request[$i]["content"]) ? $request[$i]["content"] : null;
				$response = json_decode($content, true);
				
				$reply .= $this->getResponseMessageContent($response);
			}
		}
		else if ($curl_multiple_data) {
			$response = MyCurl::getUrlContents($curl_multiple_data[0], "content_json");
			//error_log("response:" . print_r($response, 1) . "\n\n", 3, $GLOBALS["log_file_path"]);
			$reply = $this->getResponseMessageContent($response);
		}
		
		//rollback EncodingFactory
		if ($model != $this->default_model)
			$this->initEncodingFactory();
		
		return $reply;
	}
	
	public function generateRawMessage($system_content, $user_content, $previous_messages = null, $options = array()) {
		$url = !empty($options["url"]) ? $options["url"] : $this->default_url;
		$model = !empty($options["model"]) ? strtolower($options["model"]) : $this->default_model;
		$temperature = !empty($options["temperature"]) && is_numeric($options["temperature"]) ? $options["temperature"] : $this->default_temperature;
		$max_tokens = !empty($options["max_tokens"]) && is_numeric($options["max_tokens"]) ? $options["max_tokens"] : null;
		
		//prepare EncodingFactory
		if ($model != $this->default_model)
			$this->initEncodingFactory($model);
		
		//prepare messages
		$group_messages = $this->groupMessagesByChunks($system_content, $user_content, $previous_messages, $options);
		$messages = $group_messages[0];
		
		array_pop($messages);
		$messages[] = array(
			"role" => "user",
			"content" => $user_content,
		);
		//echo "<pre>";print_r($messages);die();
		
		//prepare post data
		$post_data = $this->getPostData($model, $messages, $max_tokens, $temperature);
		//echo "<pre>";print_r($post_data);die();
		
		//send request
		$curl_data = array(
			"url" => $url,
			"post" => json_encode($post_data),
			"settings" => array(
				"http_header" => array(
					"Content-Type: application/json",
					"Authorization: Bearer " . $this->openai_api_key
				),
				"do_not_prepare_post_data" => true
			)
		);
		//error_log("curl_data:" . print_r($curl_data, 1) . "\n\n", 3, $GLOBALS["log_file_path"]);
		
		$response = MyCurl::getUrlContents($curl_data, "content_json");
		//echo "<pre>";print_r($response);die();
		//error_log("response:" . print_r($response, 1) . "\n\n", 3, $GLOBALS["log_file_path"]);
		
		//rollback EncodingFactory
		if ($model != $this->default_model)
			$this->initEncodingFactory();
		
		//prepare response output
		return $this->getResponseMessageContent($response);
	}
	
	public function groupMessagesByChunks($system_content, $user_content, $previous_messages = null, $options = array()) {
		$groups = array();
		
		$model = !empty($options["model"]) ? strtolower($options["model"]) : $this->default_model;
		$max_tokens = !empty($options["max_tokens"]) && is_numeric($options["max_tokens"]) ? $options["max_tokens"] : $this->default_max_tokens;
		
		$model_tokens_limits = $this->getModelTokensLimits($model);
		
		//prepare messages
		$messages = array();
		
		if ($system_content)
			$messages[] = array(
				"role" => "system",
				"content" => $system_content,
			);
		
		if ($previous_messages) {
			if (!is_array($previous_messages))
				$previous_messages = array($previous_messages);
			
			foreach ($previous_messages as $previous_message) {
				if (is_array($previous_message) && !empty($previous_message["role"]) && !empty($previous_message["content"]))
					$messages[] = $previous_message;
				else
					$messages[] = array(
						"role" => "assistant",
						"content" => $previous_message,
					);
			}
		}
		
		$messages[] = array(
			"role" => "user",
			"content" => $user_content,
		);
		
		$messages_tokens = $this->estimateMessagesTokens($messages);
		$messages_tokens_limit = $model_tokens_limits - $max_tokens;
		
		if ($messages_tokens > $messages_tokens_limit) { //give $max_tokens of offset, so openai have space to reply something
			$user_message = array_pop($messages);
			$system_messages_tokens = $this->estimateMessagesTokens($messages);
			$user_content_tokens_limit = $model_tokens_limits - $max_tokens - $system_messages_tokens;
			
			$user_contents = $this->splitTextIntoLinesByTokens($user_content, $user_content_tokens_limit);
			
			if ($user_contents) {
				$user_contents_total = count($user_contents);
				
				foreach ($user_contents as $i => $uc) {
					$group_messages = array();
					$group_messages[] = array(
						"role" => "system",
						"content" => "current_chunk: $i, total_chunks: $user_contents_total",
					);
					$group_messages = array_merge($group_messages, $messages);
					$group_messages[] = array(
						"role" => "user",
						"content" => $uc,
					);
					$groups[] = $group_messages;
				}
			}
			else
				$groups[] = $messages;
		}
		else
			$groups[] = $messages;
		
		return $groups;
	}
	
	private function getPostData($model, $messages, $max_tokens, $temperature) {
		$messages_tokens = $this->estimateMessagesTokens($messages);
		$model_tokens_limits = $this->getModelTokensLimits($model);
		
		//prepare max_tokens
		if (!$max_tokens) {
			$max_tokens = $model_tokens_limits - $messages_tokens;
			
			if ($max_tokens < $this->default_max_tokens)
				$max_tokens = $this->default_max_tokens;
		}
		
		//error_log("model_tokens_limits:$model_tokens_limits\nmessages_tokens:$messages_tokens\nmax_tokens:$max_tokens\ndif:".($model_tokens_limits - $messages_tokens)."\n\n", 3, $GLOBALS["log_file_path"]);
		
		//prepare post_data
		$post_data = array(
			"model" => $model,
			"messages" => $messages,
			"max_tokens" => $max_tokens,
			"temperature" => $temperature
		);
		
		return $post_data;
	}
	
	private function getResponseMessageContent($response) {
		$content = "";
		
		if ($response && isset($response['choices']) && is_array($response['choices']))
			foreach ($response['choices'] as $choice) {
				if (isset($choice['message']['content']))
					$content .= $choice['message']['content'];
				
				if (isset($choice["finish_reason"]) && $choice["finish_reason"] != "stop")
					$this->addError("Request didn't finished correctly, due to reason: " . $choice["finish_reason"]);
			}
		
		return $content;
	}
	
	private function getResponseImagesUrl($response) {
		$items = array();
		
		if ($response && isset($response['data']) && is_array($response['data']))
				foreach ($response['data'] as $item) {
					if (isset($item['url'])) {
						$items[] = array(
							"url" => $item['url'],
							"description" => $item['revised_prompt'],
						);
					}
					else
						$this->addError("Request didn't finished correctly");
				}
		
		return $items;
	}
	
	public function getModelTokensLimits($model) {
		return isset(self::$available_models_tokens_limits[$model]) ? self::$available_models_tokens_limits[$model] : $this->getDefaultModelTokensLimits();
	}
	
	public function getDefaultModelTokensLimits() {
		return isset(self::$available_models_tokens_limits[$this->default_model]) ? self::$available_models_tokens_limits[$this->default_model] : null;
	}
	
	/**
	 * Splits a long string into smaller chunks based on a token limit, ensuring chunks end at complete lines.
	 *
	 * @param string $text The input message to be split.
	 * @param int $tokens_limit The maximum token count for each chunk.
	 * @return array An array of chunks, each within the token limit.
	 */
	private function splitTextIntoLinesByTokens($text, $tokens_limit) {
		if (!$tokens_limit || $tokens_limit < 0)
			return array();
		
		$str = !$text || is_string($text) || is_numeric($text) || is_bool($text) ? $text : json_encode($text);
		
		$lines = explode("\n", $str);
		$chunks = array();
		$current_chunk = '';

		foreach ($lines as $line) {
			if ($this->estimateTextTokens($line) <= $tokens_limit) {
				// Check if adding the current line exceeds the tokens_limit
				if ($this->estimateTextTokens($current_chunk) + $this->estimateTextTokens($line) > $tokens_limit) {
					$chunks[] = $current_chunk;
					$current_chunk = $line . "\n";
				} 
				else
					$current_chunk .= $line . "\n";
			} 
			else {
				// If a single line exceeds tokens_limit, split it recursively
				$split_line = $this->splitTextByTokens($line, $tokens_limit);
				
				foreach ($split_line as $part) {
					if ($this->estimateTextTokens($current_chunk) + $this->estimateTextTokens($part) > $tokens_limit) {
						$chunks[] = $current_chunk;
						$current_chunk = $part;
					} 
					else
						$current_chunk .= $part;
				}
				
				if (strlen($current_chunk))
					$current_chunk .= "\n";
			}
		}

		if (strlen($current_chunk))
			$chunks[] = $current_chunk;

		return $chunks;
	}

	/**
	 * Splits a long text into smaller chunks based on a token limit, ensuring chunks are based in words counting.
	 *
	 * @param string $text The input message to be split.
	 * @param int $tokens_limit The maximum token count for each chunk.
	 * @return array An array of chunks, each within the token limit.
	 */
	public function splitTextByTokens($text, $tokens_limit) {
		if (!$tokens_limit || $tokens_limit < 0)
			return array();
		
		$str = !$text || is_string($text) || is_numeric($text) || is_bool($text) ? $text : json_encode($text);
		
		$words = explode(' ', $str);
		$current_part = '';
		$result = array();
		
		foreach ($words as $word) {
			if (strlen($word) > $tokens_limit) {
				$result[] = $current_part;
				
				$sub_words = str_split($word, $tokens_limit);
				$current_part = array_pop($sub_words);
				
				$result = array_merge($result, $sub_words);
			}
			else if ($this->estimateTextTokens($current_part) + $this->estimateTextTokens($word) > $tokens_limit) {
				$result[] = $current_part;
				$current_part = $word . ' ';
			}
			else
				$current_part .= $word . ' ';
		}

		if (strlen($current_part))
			$result[] = $current_part;

		return $result;
	}
	
	public function estimateMessagesTokens($messages) {
		$total_tokens = 0;
		
		foreach ($messages as $message) {
			$content_tokens = $this->estimateTextTokens($message['content']);
			$total_tokens += ceil($content_tokens) + 4; // Add metadata overhead
		}
		
		$total_tokens += 2; // Add 2 tokens for assistant's reply
		
		return $total_tokens;
	}
	
	public function estimateTextTokens($text) {
		$str = !$text || is_string($text) || is_numeric($text) || is_bool($text) ? $text : json_encode($text);
		
		// Count tokens in the content (assume ~1.5 tokens per word)
		//return str_word_count($str) * 1.5;
		
		return isset($str) ? count($this->EncodingFactory->encode($str)) : 0;
	}
}
?>
