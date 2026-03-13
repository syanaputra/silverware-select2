<?php

/**
 * This file is part of SilverWare.
 *
 * PHP version >=8.1.0
 *
 * For full copyright and license information, please view the
 * LICENSE.md file that was distributed with this source code.
 *
 * @package SilverWare\Select2\Forms
 * @author Colin Tucker <colin@praxis.net.au>
 * @copyright 2017 Praxis Interactive
 * @license https://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @link https://github.com/praxisnetau/silverware-select2
 */

namespace SilverWare\Select2\Forms;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\Map;
use SilverStripe\ORM\Relation;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ViewableData;
use ArrayAccess;

/**
 * An extension of the Select2 field class for a Select2 Ajax field.
 *
 * @package SilverWare\Select2\Forms
 * @author Colin Tucker <colin@praxis.net.au>
 * @copyright 2017 Praxis Interactive
 * @license https://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @link https://github.com/praxisnetau/silverware-select2
 */
class Select2AjaxField extends Select2Field
{
    /**
     * Defines the allowed actions for this field.
     *
     * @var array
     * @config
     */
    private static $allowed_actions = [
        'search'
    ];

    /**
     * An array which defines the default Ajax configuration for instances.
     *
     * @var array
     * @config
     */
    private static $default_ajax_config = [
        'cache' => true,
        'delay' => 250
    ];

    /**
     * An array which defines the default configuration for instances.
     *
     * @var array
     * @config
     */
    private static $default_config = [
        'minimum-input-length' => 2
    ];

    /**
     * An array which holds the Ajax configuration for an instance.
     *
     * @var array
     */
    protected array $ajaxConfig = [];

    /**
     * Defines whether Ajax is enabled or disabled for the field.
     *
     * @var boolean
     */
    protected bool $ajaxEnabled = true;

    /**
     * The data class to search via Ajax.
     *
     * @var string
     */
    protected string $dataClass;

    /**
     * The ID field for the data class.
     *
     * @var string
     */
    protected string $idField = 'ID';

    /**
     * The text field to display for the data class.
     *
     * @var string
     */
    protected string $textField = 'Title';

    /**
     * The fields to search on the data class.
     *
     * @var array
     */
    protected array $searchFields = [
        'Title'
    ];

    /**
     * The fields to sort the result list by.
     *
     * @var array|string
     */
    protected array|string $sortBy = [
        'Title' => 'ASC'
    ];

    /**
     * The maximum number of records to answer.
     *
     * @var integer
     */
    protected int $limit = 256;

    /**
     * An array of filters which specify records to be excluded from the search.
     *
     * @var array
     */
    protected array $exclude = [];

    /**
     * Defines the string format to use for a result.
     *
     * @var string
     */
    protected ?string $formatResult = null;

    /**
     * Defines the string format to use for a selection.
     *
     * @var string
     */
    protected ?string $formatSelection = null;

    /**
     * Constructs the object upon instantiation.
     *
     * @param string $name
     * @param string $title
     * @param array|ArrayAccess $source
     * @param mixed $value
     */
    public function __construct(string $name, ?string $title = null, $source = [], $value = null)
    {
        // Construct Parent:

        parent::__construct($name, $title, $source, $value);

        // Define Default Ajax Config:

        $this->setAjaxConfig(self::config()->default_ajax_config);

        // Define Empty String:

        $this->setEmptyString(_t(__CLASS__ . '.SEARCH', 'Search'));
    }

    /**
     * Answers the field type for the template.
     *
     * @return string
     */
    public function Type(): string
    {
        return sprintf('select2ajaxfield %s', parent::Type());
    }

    /**
     * Defines the source for the receiver.
     *
     * @param array|ArrayAccess
     *
     * @return $this
     */
    public function setSource($source): static
    {
        if ($source instanceof DataList) {
            $this->setDataClass($source->dataClass());
        }

        return parent::setSource($source);
    }

    /**
     * Defines either the named Ajax config value, or the Ajax config array.
     *
     * @param string|array $arg1
     * @param mixed $arg2
     *
     * @return $this
     */
    public function setAjaxConfig(string|array $arg1, mixed $arg2 = null): static
    {
        if (is_array($arg1)) {
            $this->ajaxConfig = $arg1;
        } else {
            $this->ajaxConfig[$arg1] = $arg2;
        }

        return $this;
    }

    /**
     * Answers either the named Ajax config value, or the Ajax config array.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getAjaxConfig(?string $name = null): mixed
    {
        if (!is_null($name)) {
            return $this->ajaxConfig[$name] ?? null;
        }

        return $this->ajaxConfig;
    }

    /**
     * Defines the value of the ajaxEnabled attribute.
     *
     * @param boolean $ajaxEnabled
     *
     * @return $this
     */
    public function setAjaxEnabled(bool $ajaxEnabled): static
    {
        $this->ajaxEnabled = $ajaxEnabled;

        return $this;
    }

    /**
     * Answers the value of the ajaxEnabled attribute.
     *
     * @return boolean
     */
    public function getAjaxEnabled(): bool
    {
        return $this->ajaxEnabled;
    }

    /**
     * Defines the value of the dataClass attribute.
     *
     * @param string $dataClass
     *
     * @return $this
     */
    public function setDataClass(string $dataClass): static
    {
        $this->dataClass = $dataClass;

        return $this;
    }

    /**
     * Answers the value of the dataClass attribute.
     *
     * @return string
     */
    public function getDataClass(): ?string
    {
        return $this->dataClass ?? null;
    }

    /**
     * Defines the value of the idField attribute.
     *
     * @param string $idField
     *
     * @return $this
     */
    public function setIDField(string $idField): static
    {
        $this->idField = $idField;

        return $this;
    }

    /**
     * Answers the value of the idField attribute.
     *
     * @return string
     */
    public function getIDField(): string
    {
        return $this->idField;
    }

    /**
     * Defines the value of the textField attribute.
     *
     * @param string $textField
     *
     * @return $this
     */
    public function setTextField(string $textField): static
    {
        $this->textField = $textField;

        return $this;
    }

    /**
     * Answers the value of the textField attribute.
     *
     * @return string
     */
    public function getTextField(): string
    {
        return $this->textField;
    }

    /**
     * Defines the value of the searchFields attribute.
     *
     * @param array $searchFields
     *
     * @return $this
     */
    public function setSearchFields(array $searchFields): static
    {
        $this->searchFields = $searchFields;

        return $this;
    }

    /**
     * Answers the value of the searchFields attribute.
     *
     * @return array
     */
    public function getSearchFields(): array
    {
        return $this->searchFields;
    }

    /**
     * Defines the value of the sortBy attribute.
     *
     * @param array|string $sortBy
     *
     * @return $this
     */
    public function setSortBy(array|string $sortBy): static
    {
        $this->sortBy = $sortBy;

        return $this;
    }

    /**
     * Answers the value of the sortBy attribute.
     *
     * @return array
     */
    public function getSortBy(): array|string
    {
        return $this->sortBy;
    }

    /**
     * Defines the value of the limit attribute.
     *
     * @param integer $limit
     *
     * @return $this
     */
    public function setLimit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Answers the value of the limit attribute.
     *
     * @return integer
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Defines the value of the exclude attribute.
     *
     * @param array $exclude
     *
     * @return $this
     */
    public function setExclude(array $exclude): static
    {
        $this->exclude = $exclude;

        return $this;
    }

    /**
     * Answers the value of the exclude attribute.
     *
     * @return array
     */
    public function getExclude(): array
    {
        return $this->exclude;
    }

    /**
     * Defines the value of the formatResult attribute.
     *
     * @param string $formatResult
     *
     * @return $this
     */
    public function setFormatResult(?string $formatResult): static
    {
        $this->formatResult = $formatResult;

        return $this;
    }

    /**
     * Answers the value of the formatResult attribute.
     *
     * @return string
     */
    public function getFormatResult(): ?string
    {
        return $this->formatResult;
    }

    /**
     * Defines the value of the formatSelection attribute.
     *
     * @param string $formatSelection
     *
     * @return $this
     */
    public function setFormatSelection(?string $formatSelection): static
    {
        $this->formatSelection = $formatSelection;

        return $this;
    }

    /**
     * Answers the value of the formatSelection attribute.
     *
     * @return string
     */
    public function getFormatSelection(): ?string
    {
        return $this->formatSelection;
    }

    /**
     * Updates the text field, search fields and sort order to the specified field name.
     *
     * @param string $field
     * @param string $order
     *
     * @return $this
     */
    public function setDescriptor(string $field, string $order = 'ASC'): static
    {
        // Define Attributes:

        $this->setTextField($field);
        $this->setSearchFields([$field]);
        $this->setSortBy([$field => $order]);

        // Answer Self:

        return $this;
    }

    /**
     * Answers an array of data attributes for the field.
     *
     * @return array
     */
    public function getDataAttributes(): array
    {
        $attributes = parent::getDataAttributes();

        if ($this->isAjaxEnabled()) {

            foreach ($this->getFieldAjaxConfig() as $key => $value) {
                $attributes[sprintf('data-ajax--%s', $key)] = $this->getDataValue($value);
            }

        }

        return $attributes;
    }

    /**
     * Answers true if Ajax is enabled for the field.
     *
     * @return boolean
     */
    public function isAjaxEnabled(): bool
    {
        return $this->ajaxEnabled;
    }

    /**
     * Answers an HTTP response containing JSON results matching the given search parameters.
     *
     * @param HTTPRequest $request
     *
     * @return HTTPResponse
     */
    public function search(HTTPRequest $request): HTTPResponse
    {
        // Detect Ajax:

        if (!$request->isAjax()) {
            return $this->httpError(400);
        }

        // Initialise:

        $data = ['results' => []];

        // Create Data List:

        $list = $this->getList();

        // Filter Data List:

        if ($term = $request->getVar('term')) {
            $list = $list->filterAny($this->getSearchFilters($term))->exclude($this->getExclude());
        }

        // Sort Data List:

        if ($sort = $this->getSortBy()) {
            $list = $list->sort($sort);
        }

        // Limit Data List:

        if ($limit = $this->getLimit()) {
            $list = $list->limit($limit);
        }

        // Define Results:

        foreach ($list as $record) {
            $data['results'][] = $this->getResultData($record);
        }

        // Answer JSON Response:

        return $this->respond($data);
    }

    /**
     * Answers an array of search filters for the given term.
     *
     * @param string $term
     *
     * @return array
     */
    public function getSearchFilters(string $term): array
    {
        $filters = [];

        foreach ($this->getSearchFields() as $field) {
            $filters[$this->getSearchFilterName($field)] = $term;
        }

        return $filters;
    }

    /**
     * Answers the name of the search filter for the specified field.
     *
     * @param string $field
     *
     * @return string
     */
    public function getSearchFilterName(string $field): string
    {
        return (strpos($field, ':') !== false) ? $field : sprintf('%s:PartialMatch', $field);
    }

    /**
     * Loads the value of the field from the given relation.
     *
     * @param Relation $relation
     *
     * @return void
     */
    public function loadFromRelation(Relation $relation): void
    {
        parent::setValue($relation->column($this->getIDField()));
    }

    /**
     * Saves the value of the field into the given relation.
     *
     * @param Relation $relation
     *
     * @return void
     */
    public function saveIntoRelation(Relation $relation): void
    {
        $ids = [];

        if ($values = $this->getValueArray()) {
            $ids = $this->getList()->filter($this->getIDField(), $values)->getIDList();
        }

        $relation->setByIDList($ids);
    }

    /**
     * Answers true if the given data value and user value match (i.e. the value is selected).
     *
     * @param mixed $dataValue
     * @param mixed $userValue
     *
     * @return boolean
     */
    public function isSelectedValue($dataValue, $userValue): bool
    {
        if (is_array($userValue) && in_array($dataValue, $userValue, true)) {
            return true;
        }

        return parent::isSelectedValue($dataValue, $userValue);
    }

    /**
     * Answers the source array for the field options, including the empty string, if present.
     *
     * @return array
     */
    public function getSourceEmpty(): array
    {
        if (!$this->isAjaxEnabled()) {
            return parent::getSourceEmpty();
        } elseif ($this->getHasEmptyDefault()) {
            return ['' => $this->getEmptyString()];
        }

        return [];
    }

    /**
     * Answers the record identified by the given value.
     *
     * @param mixed $id
     *
     * @return ViewableData
     */
    protected function getValueRecord($id): ?ViewableData
    {
        return $this->getList()->find($this->getIDField(), $id);
    }

    /**
     * Answers an HTTP response object with the given array of JSON data.
     *
     * @param array $data
     *
     * @return HTTPResponse
     */
    protected function respond(array $data = []): HTTPResponse
    {
        return HTTPResponse::create(Convert::array2json($data))->addHeader('Content-Type', 'application/json');
    }

    /**
     * Answers the underlying list for the field.
     *
     * @return SS_List
     */
    protected function getList(): SS_List
    {
        if (!isset($this->dataClass)) {
            throw new \LogicException(sprintf('No data class has been configured for "%s".', static::class));
        }

        return DataList::create($this->dataClass);
    }

    /**
     * Answers a result data array for the given record object.
     *
     * @param ViewableData $record
     * @param boolean $selected
     *
     * @return array
     */
    protected function getResultData(ViewableData $record, bool $selected = false): array
    {
        return [
            'id' => $record->{$this->getIDField()},
            'text' => $record->{$this->getTextField()},
            'formattedResult' => $this->getFormattedResult($record),
            'formattedSelection' => $this->getFormattedSelection($record),
            'selected' => $selected
        ];
    }

    /**
     * Answers a formatted result string for the given record object.
     *
     * @param ViewableData $record
     *
     * @return string
     */
    protected function getFormattedResult(ViewableData $record): ?string
    {
        if ($format = $this->getFormatResult()) {
            return SSViewer::fromString($format)->process($record);
        }
    }

    /**
     * Answers a formatted selection string for the given record object.
     *
     * @param ViewableData $record
     *
     * @return string
     */
    protected function getFormattedSelection(ViewableData $record): ?string
    {
        if ($format = $this->getFormatSelection()) {
            return SSViewer::fromString($format)->process($record);
        }
    }

    /**
     * Converts the given data source into an array.
     *
     * @param array|ArrayAccess $source
     *
     * @return array
     */
    protected function getListMap($source): array|ArrayAccess
    {
        // Extract Map from ID / Text Fields:

        if ($source instanceof SS_List) {
            $source = $source->map($this->getIDField(), $this->getTextField());
        }

        // Convert Map to Array:

        if ($source instanceof Map) {
            $source = $source->toArray();
        }

        // Determine Invalid Types:

        if (!is_array($source) && !($source instanceof ArrayAccess)) {
            throw new \InvalidArgumentException('$source passed in as invalid type');
        }

        // Answer Data Source:

        return $source;
    }

    /**
     * Answers the field config for the receiver.
     *
     * @return array
     */
    protected function getFieldConfig(): array
    {
        $config = parent::getFieldConfig();

        if ($values = $this->getValueArray()) {

            $data = [];

            foreach ($values as $value) {

                if ($record = $this->getValueRecord($value)) {
                    $data[] = $this->getResultData($record, true);
                }

            }

            $config['data'] = $data;

        }

        return $config;
    }

    /**
     * Answers the field Ajax config for the receiver.
     *
     * @return array
     */
    protected function getFieldAjaxConfig(): array
    {
        $config = $this->getAjaxConfig();

        if (!isset($config['url'])) {
            $config['url'] = $this->Link('search');
        }

        return $config;
    }
}
