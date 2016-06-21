<?php

/*
 * This file is part of the Stinger Enity Search package.
 *
 * (c) Oliver Kotte <oliver.kotte@stinger-soft.net>
 * (c) Florian Meyer <florian.meyer@stinger-soft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace StingerSoft\EntitySearchBundle\Services;

use StingerSoft\EntitySearchBundle\Model\SearchableEntity;
use Doctrine\Common\Persistence\ObjectManager;
use StingerSoft\EntitySearchBundle\Model\Document;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Handles the creation of documents out of entities
 */
class EntityHandler {

	/**
	 *
	 * @var SearchService
	 */
	protected $searchService;

	/**
	 *
	 * @var string[string]
	 */
	protected $mapping = array();

	/**
	 *
	 * @var string[string]
	 */
	protected $cachedMapping = array();

	/**
	 * Constructor
	 *
	 * @param SearchService $searchService        	
	 */
	public function __construct(SearchService $searchService, array $mapping = array()) {
		$this->searchService = $searchService;
		foreach($mapping as $key => $config) {
			if(!isset($config['mappings'])) {
				throw new \InvalidArgumentException($key . ' has no mapping defined!');
			}
			if(!isset($config['persistence'])) {
				throw new \InvalidArgumentException($key . ' has no persistence defined!');
			}
			if(!isset($config['persistence']['model'])) {
				throw new \InvalidArgumentException($key . ' has no model defined!');
			}
			$map = array();
			foreach($config['mappings'] as $fieldKey => $fieldConfig) {
				$map[$fieldKey] = isset($fieldConfig['propertyPath']) && $fieldConfig['propertyPath'] ? $fieldConfig['propertyPath'] : $fieldKey;
			}
			
			$this->mapping[$config['persistence']['model']] = $map;
		}
	}

	/**
	 * Checks if the given object can be added to the index
	 *
	 * @param object $object        	
	 * @return boolean
	 */
	public function isIndexable($object) {
		if($object instanceof SearchableEntity) {
			return true;
		}
		if(count($this->getMapping($object)) > 0) {
			return true;
		}
		return false;
	}

	/**
	 * Tries to create a document from the given object
	 *
	 * @param ObjectManager $manager        	
	 * @param object $object        	
	 * @return boolean|Document Returns false if no document could be created
	 */
	public function createDocument(ObjectManager $manager, $object) {
		if(!$this->isIndexable($object))
			return false;
		$document = $this->getSearchService($manager)->createEmptyDocumentFromEntity($object);
		$index = $this->fillDocument($document, $object);
		if($index == false)
			return false;
		
		return $document;
	}

	/**
	 * Fills the given document based on the object
	 *
	 * @param Document $document        	
	 * @param object $object        	
	 * @return boolean
	 */
	protected function fillDocument(Document &$document, $object) {
		if($object instanceof SearchableEntity) {
			return $object->indexEntity($document);
		}
		$mapping = $this->getMapping($object);
		$accessor = PropertyAccess::createPropertyAccessor();
		foreach($mapping as $fieldName => $propertyPath) {
			$document->addField($fieldName, $accessor->getValue($object, $propertyPath));
		}
		return true;
	}

	/**
	 * Fetches the mapping for the given object including the mapping of superclasses
	 *
	 * @param object $object        	
	 * @return \StingerSoft\EntitySearchBundle\Services\string[string]
	 */
	protected function getMapping($object) {
		$clazz = get_class($object);
		if(isset($this->cachedMapping[$clazz])) {
			return $this->cachedMapping[$clazz];
		}
		$ref = new \ReflectionClass($clazz);
		
		$mapping = array();
		
		foreach($this->mapping as $className => $config) {
			if($clazz == $className || $ref->isSubclassOf($className)) {
				$mapping = array_merge($mapping, $config);
			}
		}
		
		$this->cachedMapping[$clazz] = $mapping;
		
		return $mapping;
	}

	/**
	 * Returns the search service
	 * 
	 * @return SearchService
	 */
	protected function getSearchService(ObjectManager $manager) {
		$this->searchService->setObjectManager($manager);
		return $this->searchService;
	}
}