<?php
/**
 * Form class
 *
 * @package  	AnsPress
 * @license  	http://www.opensource.org/licenses/gpl-license.php GPL v2.0 (or later)
 * @link     	http://wp3.in
 * @since 		2.0
 */

class AnsPress_Form {

    private $name;

    private $args = array();

    private $output = '';
    
    private $field;

    private $errors;

    /**
     * Initiate the class
     * @param array $args
     */
    public function __construct($args = array())
    {
        // Default args
        $defaults = array(
                //'name'         => '',
                'method'            => 'POST',
                'action'            => '',
                'is_ajaxified'      => false,
                'class'             => 'ap-form',
                'submit_button'     => __('Submit', 'ap'),
            );

        // Merge defaults args
        $this->args = wp_parse_args( $args, $defaults );

        // set the name of the form
        $this->name = $this->args['name'];

        global $ap_errors;
        $this->errors = $ap_errors;

        $this->add_default_in_field();

        $this->order_fields();
    }

    private function add_default_in_field()
    {
        if(!isset($this->args['fields']))
            return;
        
        foreach($this->args['fields'] as $k => $field){
            if(!isset($field['order']))
                $this->args['fields'][$k]['order'] = 10;
        }
    }

    /**
     * Order fields
     * @return void
     * @since 2.0
     */
    private function order_fields()
    {
        if(!isset($this->args['fields']))
            return;

        usort($this->args['fields'], function($a, $b) {
            return $a['order'] - $b['order'];
        });
    }

    /**
     * Build the form 
     * @return void
     * @since 2.0
     */
    public function build()
    {
        $this->form_head();
        $this->form_fields();
        $this->hidden_fields();
        $this->form_footer();
    }

    /**
     * FORM element
     * @return void
     * @since 2.0
     */
    private function form_head()
    {
        $attr = '';

        if($this->args['is_ajaxified'])
            $attr .= ' data-type="ap_ajax_form"';

        if(!empty($this->args['class']))
            $attr .= ' class="'.$this->args['class'].'"';

        ob_start();
        /**
         * ACTION: ap_form_before_[form_name]
         * action for hooking before form
         * @since 2.0
         */
        do_action('ap_form_before_'. $this->name);
        $this->output .= ob_get_clean();

        $this->output .= '<form name="'.$this->args['name'].'" method="'.$this->args['method'].'" action="'.$this->args['action'].'"'.$attr.'>';
    }

    /**
     * FORM footer
     * @return void
     * @since 2.0
     */
    private function form_footer()
    { 
        ob_start();
        /**
         * ACTION: ap_form_bottom_[form_name]
         * action for hooking captcha and extar fields
         * @since 2.0
         */
        do_action('ap_form_bottom_'. $this->name);
        $this->output .= ob_get_clean();

        $this->output .= '<button type="submit" class="ap-btn ap-submit-btn">'.$this->args['submit_button'].'</button>';
        $this->output .= '</form>';
    }

    private function nonce()
    {
        $this->output .=  wp_nonce_field( $this->name, '__nonce', true, false) ;
    }

    /**
     * Form hidden fields
     * @return void
     * @since 2.0
     */
    private function hidden_fields()
    {
        if($this->args['is_ajaxified'])
            $this->output .= '<input type="hidden" name="action" value="ap_submit_form">';
        
        $this->output .= '<input type="hidden" name="ap_form_action" value="'.$this->name.'">';

        $this->nonce();
    }

    /**
     * form field label
     * @return void
     * @since 2.0
     */
    private function label()
    {
        if($this->field['label'])
            $this->output .= '<label class="ap-form-label" for="'. @$this->field['name'] .'">'. @$this->field['label'] .'</label>';
    }

    /**
     * Output placeholder attribute of current field
     * @return string
     * @since 2.0
     */
    private function placeholder(){        
        return !empty($this->field['placeholder']) ? ' placeholder="'.$this->field['placeholder'].'"' : '';
    }

    /**
     * Output description of a form fields
     * @return void
     * @since 2.0
     */
    private function desc(){
        $this->output .= (!empty($this->field['desc']) ? '<p class="ap-field-desc">'.$this->field['desc'].'</p>' : '');
    }

    /**
     * Output text fields
     * @param       array  $field
     * @return      void
     * @since       2.0
     */
    private function text_field($field = array())
    {
        if(isset($field['label']))
            $this->label();

        $placeholder = $this->placeholder();
        $this->output .= '<input type="text" class="ap-form-control" value="'. @$field['value'] .'" name="'. @$field['name'] .'"'.$placeholder.' />';
        $this->error_messages();
        $this->desc();
    }

    /**
     * Checkbox field
     * @param  array  $field
     * @return void
     * @since 2.0
     */
    private function checkbox_field($field = array())
    {
        if(isset($field['label']))
            $this->label();
        
        if(!empty($field['desc']))
            $this->output .= '<div class="ap-checkbox-withdesc clearfix">';

        $this->output .= '<input type="checkbox" class="ap-form-control" value="1" name="'. @$field['name'] .'" '.checked( (bool)$field['value'], true, false ).' />';
        $this->error_messages();
        $this->desc();

        if(!empty($field['desc']))
            $this->output .= '</div>';
    }

    /**
     * output select field options
     * @param  array  $field
     * @return void
     * @since 2.0
     */
    private function select_options($field = array())
    {
        foreach($field['options'] as $k => $opt )
            $this->output .= '<option value="'.$k.'" '.selected( $k, $field['value'], false).'>'.$opt.'</option>';
    }

    /**
     * Select fields
     * @param  array  $field
     * @return void
     * @since 2.0
     */
    private function select_field($field = array())
    {
        if(isset($field['label']))
            $this->label();
        
        $this->output .= '<select class="ap-form-control" value="'. @$field['value'] .'" name="'. @$field['name'] .'" >';
        $this->output .= '<option value=""></option>';
        $this->select_options($field);
        $this->output .= '</select>';
        $this->error_messages();
        $this->desc();
    }

    /**
     * output select field options
     * @param  array  $field
     * @return void
     * @since 2.0
     */
    private function taxonomy_select_options($field = array())
    {
        $taxonomies = get_terms( $field['taxonomy'], 'orderby=count&hide_empty=0&hierarchical=0' );
        
        if($taxonomies){
            foreach($taxonomies as $tax )
                $this->output .= '<option value="'.$tax->term_id.'" '.selected( $tax->term_id, $field['value'], false).'>'.$tax->name.'</option>';
        }
    }

    /**
     * Taxonomy select field
     * @param  array  $field
     * @return void
     * @since 2.0
     */
    private function taxonomy_select_field($field = array())
    {
        if(isset($field['label']))
            $this->label();
        
        $this->output .= '<select class="ap-form-control" value="'. @$field['value'] .'" name="'. @$field['name'] .'" >';
        $this->output .= '<option value=""></option>';
        $this->taxonomy_select_options($field);
        $this->output .= '</select>';
        $this->error_messages();
        $this->desc();
    }

    /**
     * textarea fields
     * @param       array  $field
     * @return      void
     * @since       2.0
     */
    private function textarea_field($field = array())
    {
        if(isset($field['label']))
            $this->label();

        $placeholder = $this->placeholder();
        $this->output .= '<textarea rows="'. @$field['rows'] .'" class="ap-form-control" name="'. @$field['name'] .'"'.$placeholder.'>'. @$field['value'] .'</textarea>';
        $this->error_messages();
        $this->desc();
    }

    /**
     * Create wp_editor field
     * @param  array  $field
     * @return void      
     * @since 2.0
     */
    private function editor_field($field = array())
    {
        if(isset($field['label']))
            $this->label();

        /**
         * FILTER: ap_pre_editor_settings
         * Can be used to mody wp_editor settings
         * @var array
         * @since 2.0
         */
        $field['settings']['tinymce'] = array( 
            'content_css' => ap_get_theme_url('css/editor.css') 
       );
        $settings = apply_filters('ap_pre_editor_settings', $field['settings'] );

        // Turn on the output buffer
        ob_start();
        echo '<div class="ap-editor">';
        wp_editor( $field['value'], $field['name'], $field['settings'] );
        echo '</div>';
        $this->output .= ob_get_clean();
        $this->error_messages();
        $this->desc();
    }
    /**
     * For creating hidden input fields
     * @param  array  $field
     * @return void
     * @since 2.0
     */
    private function hidden_field($field = array()){
        $this->output .= '<input type="hidden" value="'. @$field['value'] .'" name="'. @$field['name'] .'" />';
    }

    /**
     * Check if current field have any error
     * @return boolean
     * @since 2.0
     */
    private function have_error(){
        if(isset($this->errors[$this->field['name']]))
            return true;

        return false;
    }
    private function error_messages(){
        if(isset($this->errors[$this->field['name']])){
            $this->output .= '<div class="ap-form-error-messages">';
            
            foreach($this->errors[$this->field['name']] as $error)
                $this->output .= '<p class="ap-form-error-message">'. $error .'</p>';

            $this->output .= '</div>';
        }
    }

    /**
     * Out put all form fields based on on their type
     * @return void
     * @since  2.0
     */
    private function form_fields()
    {
        /**
         * FILTER: ap_pre_form_fields
         * Provide filter to add or override form fields before output.
         * @var array
         * @since 2.0
         */
        $this->args['fields'] =  apply_filters('ap_pre_form_fields', $this->args['fields'] );
        
        foreach($this->args['fields'] as $field){

            $this->field = $field;

            $error_class = $this->have_error() ? ' ap-have-error' : '';
           
            switch ($field['type']) {

                case 'text':
                    $this->output .= '<div class="ap-form-fields'.$error_class.'">';
                    $this->text_field($field);
                    $this->output .= '</div>';
                    break;

                case 'checkbox':
                    $this->output .= '<div class="ap-form-fields'.$error_class.'">';
                    $this->checkbox_field($field);
                    $this->output .= '</div>';
                    break;

                case 'select':
                    $this->output .= '<div class="ap-form-fields'.$error_class.'">';
                    $this->select_field($field);
                    $this->output .= '</div>';
                    break;

                case 'taxonomy_select':
                    $this->output .= '<div class="ap-form-fields'.$error_class.'">';
                    $this->taxonomy_select_field($field);
                    $this->output .= '</div>';
                    break;

                case 'textarea':
                    $this->output .= '<div class="ap-form-fields'.$error_class.'">';
                    $this->textarea_field($field);
                    $this->output .= '</div>';
                    break;

                case 'editor':
                    $this->output .= '<div class="ap-form-fields'.$error_class.'">';
                    $this->editor_field($field);
                    $this->output .= '</div>';
                    break;

                case 'hidden':
                    $this->hidden_field($field);
                    break;
                
                default:
                    /**
                     * FILTER: ap_form_fields_[type]
                     * filter for custom form field type
                     */
                    $this->output .= apply_filters( 'ap_form_fields_'.$field['type'],  $field);
                    break;
            }            
        }
    }

    /**
     * Output form
     * @return string
     * @since 2.0
     */
    public function get_form()
    {
        if(empty($this->args['fields']))
            return __('No fields found', 'ap');

        $this->build();

        return $this->output;
    }

}


