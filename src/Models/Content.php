<?php

require_once __DIR__ . '/../Database.php';

class Content {
    protected $db;
    protected $table = 'content';
	protected $content_types;

    public function __construct($db) {
        $this->db = $db;
		$this->content_types = $this->db->fetchAll("SELECT * FROM content_type");
    }

    public function getAll($type = null, $author = null, $industry = null, $contractor = null) {
        $sql = "SELECT * FROM {$this->table} WHERE date_deleted is null";
        $params = [];

		$conditions = '';
        if ($type) {
			$type_id = $this->isTypeValid($type);
			if (!$type_id) return "Type not valid";
            $conditions .= " AND type_id = :type";
            $params[':type'] = $type_id;
        }

		if ($author) {
			$author_id = $this->getAuthorID($author);
			if (!$author_id) return "Author not in system";
			$conditions .= " AND author_id = :author";
			$params[':author'] = $author_id;
		}

		if ($industry) {
			$industry_id = $this->industryInSystem($industry);
			if (!$industry_id) return "Industry not in system";
			$conditions .= " AND industry_id = :industry";
			$params[':industry'] = $industry_id;
		}

		if ($contractor) {
			$contractor_id = $this->contractorInSystem($contractor);
			if (!$contractor_id) return "Contractor not in system";
			$conditions.= " AND contractor_id = :contractor";
			$params[':contractor'] = $contractor_id;
		}

		$sql .= $conditions;
        $sql .= " ORDER BY date_edited DESC";

		return $this->db->fetchAll($sql, $params);
    }

    public function getById($id) {
        $sql = "SELECT content.*, authors.name, content_type.type FROM {$this->table} join authors on authors.id = content.author_id join content_type on content_type.id = content.type_id WHERE content.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }

	private function isTypeValid($type)
	{
		foreach ($this->content_types as $contentType) {

			if ($contentType['type'] === $type) {
				return $contentType['id'];
			}
		}

		return false;
	}

	private function getContentTypes() {
		return array_column($this->content_types, 'type');
	}

	private function contractorInSystem($contractor) {
		$sql = "SELECT id FROM contractors WHERE UPPER(name) = :name";
		$id = $this->db->fetchOne($sql, [':name' => strtoupper($contractor)]);
		return $id == null ? false : $id['id'];
	}

	private function industryInSystem($industry) {
		$sql = "SELECT id FROM industry WHERE UPPER(industry) = :name";
		$id = $this->db->fetchOne($sql, [':name' => strtoupper($industry)]);
		return $id == null ? false : $id['id'];
	}

	private function getIndustryId($industry) {
		$id = $this->industryInSystem($industry);
		if (!$id) {
			$sql_insert = "INSERT INTO industry (industry) values (:name)";
			$this->db->execute($sql_insert, [':name' => $industry]);
			$id=$this->db->lastInsertId();
		}
		return $id;
	}

	private function getAuthorID($author)
	{
		$sql = "SELECT id FROM authors WHERE upper(name) = :name";
		$id = $this->db->fetchOne($sql, [':name' => strtoupper($author)]);
		return $id == null ? false : $id['id'];
	}

	private function generateSlug($title, $type_id) {
		$title = str_replace(' ', '-', strtolower($title));
		$slug = urlencode($title);
		$existing_slug = $this->slugExists($slug, $type_id);
		if ($existing_slug) {

			// Ends with -number ?
			if (preg_match('/-(\d+)$/', $slug, $matches)) {
				$number = (int)$matches[1] + 1;
				$newTitle = preg_replace('/-\d+$/','-' . $number, $slug);

				return $this->generateSlug($newTitle, $type_id);
			}

			// No numeric suffix → add -1
			return $this->generateSlug($slug . '-1', $type_id);
		}
		return $slug;
	}

	private function slugExists($slug, $type_id){
		$sql = "SELECT content_id FROM slug_history WHERE slug = :slug and type_id=:type_id";
		$existing_slug = $this->db->fetchOne($sql, [':slug' => $slug, ':type_id' => $type_id]);
		return $existing_slug ? $existing_slug['content_id'] : false;
	}

	private function updateSlugHistory($id, $slug, $type) {
		$sql = "INSERT INTO slug_history (content_id, slug, type_id) VALUES (:id, :slug, :type)";
		$this->db->execute($sql, [':id' => $id, ':slug' => $slug, ':type' => $type]);
	}

	private function validateTypeConditions($type_id, $data, $old_data=null)
	{
		switch ($type_id) {
			case 1: //blog_post
				if (!isset($data['industry']) && !isset($old_data['industry'])) {
					return ['success' => 0, 'error' => 'Blog post must have industry'];
				}
				break;
			case 2: //article - no additional requirements
				break;
			case 3: //experience
				if (!isset($data['contractor']) && !isset($old_data['contractor'])) {
					return ['success' => 0, 'error' => 'Experience must have contractor'];
				}
				break;
			case 4: //cost_guide
				if ((!isset($data['min_cost']) || !isset($data['max_cost'])) && (!isset($old_data['min_cost']) || !isset($old_data['max_cost']) )) {
					return ['success' => 0, 'error' => 'Cost guide must have min_cost and max_cost'];
				}
				break;
		}
		return ['success' => 1];
	}

	private function validate_field($key, $value)
	{
		if ($key == 'author')
		{
			$author = $this->getAuthorID($value);
			if (!$author) {
				return ['success'=>0, 'error' => 'Author not known in the system'];
			}
			$key = 'author_id';
			$value = $author;
		}
		if ($key == 'industry') {
			$industry_id=$this->getIndustryId($value);
			$key = 'industry_id';
			$value = $industry_id;
		}
		if ($key == 'contractor') {
			$contractor_id = $this->contractorInSystem($value);
			if (!$contractor_id) {
				return ['success' => 0, 'error' => 'Contractor not known in the system'];
			}
			$key = 'contractor_id';
			$value = $contractor_id;
		}
		if ($key == 'min_cost')
		{
			if(!is_numeric($value)) {
				return ['success' => 0, 'error'=>'min_cost must be an integer'];
			}
		}
		if ($key == 'max_cost')
		{
			if(!is_numeric($value)) {
				return ['success' => 0, 'error'=>'max_cost must be an integer'];
			}
		}
		return ['success' => 1, 'key' => $key, 'value' => $value];
	}


    public function create($data) {
		$type = $this->isTypeValid($data['type']);
		if (!$type) {
			$all_types = $this->getContentTypes();
			return ['success'=>0, 'error' => 'Invalid content type, content type must be one of the following: '.implode(', ', $all_types)];
		}
		//Make sure all required fields are present based on type
		$validated = $this->validateTypeConditions($type, $data);
		if ($validated['success'] == 0) {
			return $validated;
		}
		$author = $this->getAuthorID($data['author']);
		if (!$author) {
			return ['success'=>0, 'error' => 'Author not known in the system'];
		}
		//Validate each field
		$possible_keys = ['title', 'author', 'body', 'industry', 'contractor', 'min_cost', 'max_cost' ];
		foreach ($data as $key => $value) {
			if (in_array($key, $possible_keys)) {
				$validated = $this->validate_field($key, $value);
				if ($validated['success'] == 0) return $validated;
				$data[$validated['key']] = $validated['value'];
			}
		}

		$data['industry_id'] = $data['industry_id'] ?? null;
		$data['contractor_id'] = $data['contractor_id'] ?? null;
		$data['min_cost'] = $data['min_cost'] ?? null;
		$data['max_cost'] = $data['max_cost'] ?? null;

        $sql = "INSERT INTO {$this->table} (type_id, author_id, title, body, industry_id, contractor_id, min_cost, max_cost, slug, date_edited) 
                VALUES (:type_id, :author_id, :title, :body, :industry_id, :contractor_id, :min_cost, :max_cost, :slug, NOW())";

		$slug = $this->generateSlug($data['title'], $type);
        $params = [
            ':type_id' => $type,
            ':author_id' => $author,
			'title' => $data['title'],
            ':body' => $data['body'],
            ':industry_id' => $data['industry_id'],
            ':contractor_id' => $data['contractor_id'],
            ':min_cost' => $data['min_cost'],
            ':max_cost' => $data['max_cost'],
			':slug' => $slug
        ];

        $this->db->execute($sql, $params);
        $id = $this->db->lastInsertId();
		$this->updateSlugHistory($id, $slug, $type);
		return ['success'=> 1, 'id'=>$id];
    }

    public function update($id, $data) {
		$old_data = $this->getById($id);
        $fields = [];
        $params = [':id' => $id];

		if (isset($data['type'])) {
			$type = $this->isTypeValid($data['type']);
			if (!$type) {
				$all_types = $this->getContentTypes();
				return ['success'=>0, 'error' => 'Invalid content type, content type must be one of the following: '.implode(', ', $all_types)];
			}
			$validated = $this->validateTypeConditions($type, $data, $old_data);
			if ($validated['success'] == 0) {
				return $validated;
			}
			$fields[] = "type_id = :type";
			$params[':type'] = $type;
			//make sure the slug for this does not have a duplicate in the new type
			if (!isset($data['slug'])){
				$existing_id = $this->slugExists($old_data['slug'], $type);
				if($id && $existing_id != $id) {
					$data['slug'] = $this->generateSlug($old_data['slug'], $type);
				}
			}
		}
		$possible_keys = ['title', 'author', 'body', 'industry', 'contractor', 'min_cost', 'max_cost' ];
		foreach ($data as $key => $value) {
			if (in_array($key, $possible_keys)) {
					$validated = $this->validate_field($key, $value);
					if ($validated['success'] == 0) return $validated;
					$fields[] = "{$validated['key']} = :{$validated['key']}";
					$params[':'. $validated['key']] = $validated['value'];

			}
		}

		$type_id = $type ?? $old_data['type_id'];
		if (isset($data['slug']))
		{
			$data['slug'] = str_replace(' ', '-', $data['slug']);
			$slugExists = $this->slugExists($data['slug'], $type_id);
			if($slugExists && $slugExists != $id) {
				return ['success' => 0, 'error'=>'slug already exists'];
			}
			$fields[] = "slug = :slug";
			$params[':slug'] = $data['slug'];
		}

        $fields[] = "date_edited = NOW()";

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $updated = $this->db->execute($sql, $params);
		if ($updated) {
			if (isset($data['slug'])) $this->updateSlugHistory($id, $data['slug'], $type_id);
			return ['success'=> 1];
		}
		return ['success'=> 0, 'error'=>'Failed to update content'];
    }

    public function delete($id) {
        $sql = "UPDATE {$this->table} SET date_deleted =  now() WHERE id = :id";
        return $this->db->execute($sql, [':id' => $id]);
    }

	public function publish($id) {
		$sql = "UPDATE {$this->table} SET date_published = NOW() WHERE id = :id";
		$updated = $this->db->execute($sql, [':id' => $id]);
		return $updated ? ['success'=> 1] : ["success"=>0, 'error'=>'Failed to publish content'];
	}

	public function addAuthor($data){
		if (!isset($data['name']) || !isset($data['email'])){
			return ['success'=>0, 'error' => 'Missing required fields: name, email'];
		}
		$sql = "INSERT INTO authors (name, email) VALUES (:name, :email)";
		$this->db->execute($sql, [':name' => $data['name'], ':email' => $data['email']]);
		$id = $this->db->lastInsertId();
		return ['success'=> 1, 'id'=>$id];
	}

	public function getAuthor($id) {
		$sql = "SELECT * FROM authors WHERE id = :id";
		return $this->db->fetchOne($sql, [':id' => $id]);
	}

	public function getBySlug($type, $slug) {
		$type_id = $this->isTypeValid($type);
		if (!$type_id) return ['success'=> 0, 'error'=>'Invalid content type'];
		$sql = "SELECT {$this->table}.*  FROM {$this->table} JOIN slug_history ON {$this->table}.id = slug_history.content_id WHERE slug_history.type_id = :type_id AND slug_history.slug = :slug and date_published is not null and date_deleted is null";
		$result = $this->db->fetchOne($sql, [':type_id' => $type_id, ':slug' => $slug]);
		if (empty($result)) return ["success"=>0, "error" =>"Content not found"];
		return ["success"=>1, "result"=>$result];
	}

}
