<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\Field as ASTField;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\Flag;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\QueryException;
use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;

/**
 * @todo Check for private fields and only search on them if allowed
 */
class QueryContext
{
    /** @var Structure */
    private $structure;
    /** @var array */
    private $locales;
    /** @var string */
    private $queryLocale;
    /** @var array */
    private $fields;
    /** @var  SearchEngineOptions */
    private $options;

    /**
     * @param SearchEngineOptions|null $options
     * @param Structure $structure
     * @param array $locales
     * @param $queryLocale
     * @param array $fields
     */
    public function __construct($options, Structure $structure, array $locales, $queryLocale, array $fields = null)
    {
        $this->structure = $structure;
        $this->locales = $locales;
        $this->queryLocale = $queryLocale;
        $this->fields = $fields;
        $this->options = $options;
    }

    public function getDataboxes()
    {
        return $this->structure->getDataboxes();
    }

    public function narrowToFields(array $fields)
    {
        if (is_array($this->fields)) {
            // Ensure we are not escaping from original fields restrictions
            $fields = array_intersect($this->fields, $fields);

            if (!$fields) {
                throw new QueryException('Query narrowed to non available fields');
            }
        }

        return new static($this->options, $this->structure, $this->locales, $this->queryLocale, $fields);
    }

    /**
     * @return Field[]
     */
    public function getUnrestrictedFields()
    {
        // TODO Restore search optimization by using "caption_all" field
        // (only when $this->fields is null)
        return $this->filterFields($this->structure->getUnrestrictedFields());
    }

    public function getPrivateFields()
    {
        return $this->filterFields($this->structure->getPrivateFields());
    }

    public function getHighlightedFields()
    {
        return $this->filterFields($this->structure->getAllFields());
    }

    /**
     * @param Field[] $fields
     * @return Field[]
     */
    private function filterFields(array $fields)
    {
        if ($this->fields !== null) {
            $fields = array_intersect_key($fields, array_flip($this->fields));
        }

        return array_values($fields);
    }

    public function get($name)
    {
        if ($name instanceof ASTField) {
            $name = $name->getValue();
        }

        return $this->structure->get($name);
    }

    public function getFlag($name)
    {
        if ($name instanceof Flag) {
            $name = $name->getName();
        }

        return $this->structure->getFlagByName($name);
    }

    public function getMetadataTag($name)
    {
        return $this->structure->getMetadataTagByName($name);
    }

    /**
     * @todo Maybe we should put this logic in Field class?
     */
    public function localizeField(Field $field)
    {
        $ret = null;
        $index_field = $field->getIndexField();

        switch($field->getType()) {
            case FieldMapping::TYPE_STRING:
                $ret = $this->localizeFieldName($index_field);
                break;

            case FieldMapping::TYPE_DATE:
            case FieldMapping::TYPE_DOUBLE:
                $ret = [
                    $index_field . '.light',
                    $index_field
                ];
                break;

            default:
                $ret = [$index_field];
                break;
        }

        return $ret;
    }

    public function truncationField(Field $field)
    {
        if($this->options && $this->options->useTruncation()) {
            return [sprintf('%s.truncated', $field->getIndexField())];
        }
        else {
            return [];
        }
    }

    private function localizeFieldName($field)
    {
        $fields = array();
        foreach ($this->locales as $locale) {
            $boost = ($locale === $this->queryLocale) ? '^5' : '';
            $fields[] = sprintf('%s.%s%s', $field, $locale, $boost);
        }

        // TODO Put generic analyzers on main field instead of "light" sub-field
        $fields[] = sprintf('%s.light^10', $field);

        return $fields;
    }

    public function getFields()
    {
        return $this->fields;
    }
}
