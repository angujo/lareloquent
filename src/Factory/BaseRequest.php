<?php

namespace Angujo\Lareloquent\Factory;

use Angujo\Lareloquent\Enums\DataType;
use Angujo\Lareloquent\LarEloquent;
use Angujo\Lareloquent\Models\DBColumn;
use Angujo\Lareloquent\Models\DBTable;
use Angujo\Lareloquent\Models\GeneralTag;
use Angujo\Lareloquent\Path;
use Illuminate\Foundation\Http\FormRequest;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlock\Tag\LicenseTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\FileGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ValueGenerator;
use function Angujo\Lareloquent\flatten_array;
use function Angujo\Lareloquent\method_name;
use function Angujo\Lareloquent\model_file;
use function Angujo\Lareloquent\model_name;
use function Angujo\Lareloquent\str_equal;
use function Angujo\Lareloquent\tag;

class BaseRequest extends FileCreator
{
    private array $rules    = [];
    private array $messages = [];

    public static array $default_messages = [
        'mac_address' => 'Ensure valid mac address is entered!',
        'uuid'        => 'Invalid UUID has been entered!',
        'file'        => 'File for :attribute is missing or not uploaded!',
        'required'    => ":attribute is required and cannot be null",
        'email'       => "Ensure a valid email is entered!",
        'integer'     => "Only integers allowed for :attribute field!",
        'numeric'     => "Only numeric entries allowed for :attribute field!",
        'string'      => "Allow strings allowed in :attribute",
        'json'        => "Only valid json allowed in :attribute",
        'accepted'    => "Allowed values for :attribute are 'yes', 'on', 1, or 'true'",
        'active_url'  => "Check :attribute contains an active URL",
        'url'         => "Check :attribute is a valid URL",
        'recaptcha'   => "Invalid reCaptcha value",
        'ip'          => "Only valid IP address allowed for :attribute",
        'ipv4'        => "Only valid IPv4 address allowed for :attribute",
        'ipv6'        => "Only valid IPv6 address allowed for :attribute",
        'alpha'       => "Alphabetic characters allowed at :attribute",
        'alpha_dash'  => ":attribute only allows alpha-numeric characters, dash, underscore",
        'alpha_num'   => ":attribute only allows Alpha-numeric characters only",
        'array'       => "Only arrays allowed at :attribute",
        'distinct'    => "Allow array value without duplicate value",
        'date'        => ":attribute Must be a valid, non-relative date",
        'boolean'     => "Allow boolean value only",
        'timezone'    => "Only Valid timezone allowed",
        'image'       => "Must be an image like jpeg, png, bmp, gif, svg, or webp",
        'confirmed'   => "Matching fields like password match with password_confirmation",
        'dimensions'  => "Must be an image with defined dimensions.",
        'sometimes'   => "Only validate when value exist",
        'max'         => "Only a maximum of :value characters allowed!",
        'min'         => "Only a minimum of :value characters allowed!",
        'regex'       => "Entries for :attribute have an invalid format!",
        'after'       => "Check given date at :attribute is after :value",
        'before'      => "Check given date at :attribute is before :value",
        'date_format' => "Check the date format at :attribute is valid!",
        #region custom

        'lt'                   => "Allow less value than the given field",
        'lte'                  => "Allow less or equal value than the given field",
        'gt'                   => "Allow greater value than the given field",
        'gte'                  => "Allow greater or equal value than the given field",
        'between'              => "Check number between",
        'different'            => "Given value must have a different value than fieldname",
        'digits'               => "Numeric & exact length of value",
        'digits_between'       => "Numeric & between min, max",
        'in'                   => "Value must be included given comma separated list",
        'not_in'               => "Value must not be included given comma separated list",
        'mimes'                => "Example 'photo' => 'mimes:jpeg,bmp,png'",
        'mimetypes'            => "Example 'photo' => 'image/png'",
        'required_if'          => "Required only if another_field value is same",
        'required_with'        => "Required and exist any of foo,bar,...",
        'required_with_all'    => "Required and exist all foo,bar,...",
        'required_without'     => "Required and any value without these foo,bar,...",
        'required_without_all' => "Required and any value without all foo,bar,...",
        'same'                 => "Value must match with the field",
        'size'                 => "It is dynamic.",
        'password'             => "Must match with auth user password according to guard name.",
        'unique'               => "Unique value check on table.",
        'exists'               => "Check value already exist. Example: 'email' => 'exists:users,email'",
        #endregion custom
    ];

    public function __construct()
    {
        $this->name         = model_name(LarEloquent::config()->base_request_prefix.'_'.LarEloquent::config()->request_suffix);
        $this->namespace    = implode('\\', [LarEloquent::config()->request_namespace, model_name(LarEloquent::config()->base_request_prefix)]);
        $this->parent_class = FormRequest::class;
        $this->dir          = Path::Combine(LarEloquent::config()->requests_dir, model_name(LarEloquent::config()->base_request_prefix));
        parent::__construct();
        $this->class->setAbstract(true);
    }

    private function classDoc()
    {
        return (new DocBlockGenerator())
            ->setShortDescription('Generated Request file for abstracting all requests ')
            ->setLongDescription('This is an auto-generated class and should not be modified external. All changes will be overwritten in next run.')
            ->setTag((new LicenseTag(licenseName: 'MIT')));
    }

    private function authorizeMethod()
    {
        return (new MethodGenerator('authorize'))
            ->setAbstract(true)
            ->setDocBlock((new DocBlockGenerator())
                              ->setShortDescription('Determine if the user is authorized to make this request.')
                              ->setTag(GeneralTag::returnTag('bool')))
            ->setReturnType('bool');
    }

    private function messagesMethod()
    {
        return (new MethodGenerator('messages'))
            ->setDocBlock((new DocBlockGenerator())
                              ->setShortDescription('Custom message for validation')
                              ->setTag(GeneralTag::returnTag('array')))
            ->setBody('return '.(new ValueGenerator($this->getMessages(), ValueGenerator::TYPE_ARRAY_SHORT))->setIndentation('    ')->generate().';');
    }

    public function getMessages()
    {
        return self::$default_messages;

    }

    private function compile()
    {
        $this->class->setDocBlock($this->classDoc())
                    ->addMethodFromGenerator($this->authorizeMethod());
        // ->addMethodFromGenerator($this->messagesMethod());
        return $this;
    }

    public static function Write()
    : void
    {
        (new self())->compile()->_write();
    }
}