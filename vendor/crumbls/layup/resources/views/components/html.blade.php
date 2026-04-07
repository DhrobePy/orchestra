<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif class="{{ \Crumbls\Layup\View\BaseView::visibilityClasses($data['hide_on'] ?? []) }} {{ $data['class'] ?? '' }}" style="{{ \Crumbls\Layup\View\BaseView::buildInlineStyles($data) }}" {!! \Crumbls\Layup\View\BaseView::animationAttributes($data) !!}>
    {!! $data['content'] ?? '' !!}
</div>
