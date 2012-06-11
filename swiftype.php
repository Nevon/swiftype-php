<?php 
namespace Swiftype;

class SwiftypeClient {
	private $username;
	private $password;
	private $api_key;
	private $host;
	private $api_base_path;

	public function __construct($username, $password, $api_key, $host = 'http://api.swiftype.com', $api_base_path = '/api/v1/') {
		$this->username = $username;
		$this->password = $password;
		$this->api_key = $api_key;
		$this->host = $host;
		$this->api_base_path = $api_base_path;
	}

	public function engines() {
		return $this->get($this->engines_path());
	}

	public function create_engine($engine_id) {
		$engine = array(
			'engine' => array(
				'name' => $engine_id
			)
		);

		return $this->post($this->engines_path(), array(), $engine);
	}

	//Broken
	public function destroy_engine($engine_id) {
		return $this->delete($this->engine_path($engine_id));
	}

	public function document_types($engine_id) {
		return $this->get($this->document_types_path($engine_id));
	}

	public function create_document_type($engine_id, $document_type_id) {
		$document_type = array(
			'document_type' => array(
				'name' => $document_type_id
			)
		);
		return $this->post($this->document_types_path($engine_id), array(), $document_type);
	}

	//Broken
	public function destroy_document_type($engine_id, $document_type_id) {
		return $this->delete($this->document_type_path($engine_id, $document_type_id));
	}

	public function documents($engine_id, $document_type_id) {
		return $this->get($this->documents_path($engine_id, $document_type_id));
	}

	//Broken
	public function create_document($engine_id, $document_type_id, $document = array()) {
		return $this->post($this->documents_path($engine_id, $document_type_id), array(), array('document' => $document));
	}

	//Broken
	public function create_or_update_document($engine_id, $document_type_id, $document = array()) {
		return $this->post($this->documents_path($engine_id, $document_type_id).'/create_or_update', array(), array('document' => $document));
	}

	//Not tested yet
	public function update_document($engine_id, $document_type_id, $document_id, $fields = array()) {
		return $this->put($this->document_path($engine_id, $document_type_id, $document_id).'/update_fields', array(), array('fields' => $fields));
	}

	//Not tested yet
	public function update_documents($engine_id, $document_type_id, $documents = array()) {
		return $this->put($this->documents_path($engine_id, $document_type_id).'/bulk_update', array(), array('documents' => $documents));
	}

	//Broken
	public function destroy_document($engine_id, $document_type_id, $document_id) {
		return $this->delete($this->document_path($engine_id, $document_type_id, $document_id));
	}

	//Not tested yet
	public function destroy_documents($engine_id, $document_type_id, $document_ids = array()) {
		return $this->post($this->documents_path($engine_id, $document_type_id).'/bulk_destroy', array(), array('documents' => $document_ids));
	}

	public function search($engine_id, $query, $options = array()) {
		$query_string = array('q' => $query);
		$full_query = array_merge($query_string, $options);
		return $this->get($this->search_path($engine_id), array(), $full_query);
	}

	public function suggest($engine_id, $query, $options = array()) {
		$query_string = array('q' => $query);
		$full_query = array_merge($query_string, $options);
		return $this->get($this->suggest_path($engine_id), array(), $full_query);
	}

	private function search_path($engine_id) {
		return 'engines/'.$engine_id.'/search';
	}

	private function suggest_path($engine_id) {
		return 'engines/'.$engine_id.'/suggest';
	}

	private function engines_path() {
		return 'engines';
	}

	private function engine_path($engine_id) {
		return 'engines/'.$engine_id;
	}

	private function document_type_path($engine_id, $document_type_id) {
		return $this->engine_path($engine_id).'/document_types/'.$document_type_id;
	}

	private function document_types_path($engine_id) {
		return $this->engine_path($engine_id).'/document_types';
	}

	private function document_path($engine_id, $document_type_id, $document_id) {
		return $this->document_type_path($engine_id, $document_type_id).'/documents/'.$document_id;
	}

	private function documents_path($engine_id, $document_type_id) {
		return $this->document_type_path($engine_id, $document_type_id).'/documents';
	}

	private function get($path, $params = array(), $data = array()) {
		return $this->request('GET', $path, $params, $data);
	}

	private function post($path, $params = array(), $data = array()) {
		return $this->request('POST', $path, $params, $data);
	}

	private function delete($path, $params = array(), $data = array()) {
		return $this->request('DELETE', $path, $params, $data);
	}

	private function put($path, $params = array(), $data = array()) {
		return $this->request('PUT', $path, $params, $data);
	}

	private function request($method, $path, $params = array(), $data = array()) {
		$full_path = $this->host.$this->api_base_path.$path.'.json';
		
		//Use the api key if we have it.
		if ($this->api_key != null) {
			$params['auth_token'] = $this->api_key;
		}

		//Build the query string
		$query = http_build_query($params);

		if ($query) {
			$full_path .= '?' . $query;
		}

		//Initiate the request
		$request = curl_init($full_path);

		//Use basic http auth if we have no api key
		//Throw an exception if we have nothing with which to authenticate ourselves
		if ($this->api_key === null && $this->username != null && $this->password != null) {
			curl_setopt($request, CURLOPT_USERPWD, $this->username.':'.$this->password);
		} elseif ($this->api_key === null && $this->username === null && $this->password === null) {
			throw new \Exception('Authorization required.');
		}

		curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($request, CURLOPT_FAILONERROR, true);

    	$body = ($data) ? json_encode($data) : '';

		curl_setopt($request, CURLOPT_HTTPHEADER, array('Content-type: application/json'));

		if ($method === 'POST') {
			curl_setopt($request, CURLOPT_POST, true);
			curl_setopt($request, CURLOPT_POSTFIELDS, $body);
		} elseif ($method === 'DELETE') {
			curl_setopt($request, CURLOPT_CUSTOMREQUEST, 'DELETE');
		} elseif ($method === 'PUT') {
			curl_setopt($request, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($request, CURLOPT_POSTFIELDS, $body);
		}

		$response = curl_exec($request);
		$error = curl_error($request);
    	
    	if ($error) {
      		throw new \Exception("Sending message failed. Error: ". $error);
    	}
    
    	$http_status = curl_getinfo($request,CURLINFO_HTTP_CODE);
    	curl_close($request);
    
    	if ($http_status / 100 === 2) {
    		$final = json_decode($response);
    		return ($final === null) ? 'The JSON response could not be parsed: '.$response : $final;
    	} elseif ($http_status === 401) {
    		throw new \Exception('Authorization required.');
    	} else {
      		throw new \Exception("Couldn't send message, got response code: ". $http_status. " response: ".$response);
    	}
	}
}

?>