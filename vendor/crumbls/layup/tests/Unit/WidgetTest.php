<?php

declare(strict_types=1);

use Crumbls\Layup\View\AccordionWidget;
use Crumbls\Layup\View\AlertWidget;
use Crumbls\Layup\View\AnchorWidget;
use Crumbls\Layup\View\AnimatedHeadingWidget;
use Crumbls\Layup\View\AudioWidget;
use Crumbls\Layup\View\AvatarGroupWidget;
use Crumbls\Layup\View\BackToTopWidget;
use Crumbls\Layup\View\BadgeWidget;
use Crumbls\Layup\View\BannerWidget;
use Crumbls\Layup\View\BarCounterWidget;
use Crumbls\Layup\View\BeforeAfterWidget;
use Crumbls\Layup\View\BlockquoteWidget;
use Crumbls\Layup\View\BlurbWidget;
use Crumbls\Layup\View\BreadcrumbsWidget;
use Crumbls\Layup\View\ButtonWidget;
use Crumbls\Layup\View\CallToActionWidget;
use Crumbls\Layup\View\CardWidget;
use Crumbls\Layup\View\ChangelogWidget;
use Crumbls\Layup\View\CodeWidget;
use Crumbls\Layup\View\ComparisonTableWidget;
use Crumbls\Layup\View\ContactFormWidget;
use Crumbls\Layup\View\ContentToggleWidget;
use Crumbls\Layup\View\CookieConsentWidget;
use Crumbls\Layup\View\CountdownWidget;
use Crumbls\Layup\View\DividerWidget;
use Crumbls\Layup\View\EmbedWidget;
use Crumbls\Layup\View\FaqWidget;
use Crumbls\Layup\View\FeatureGridWidget;
use Crumbls\Layup\View\FeatureListWidget;
use Crumbls\Layup\View\FileDownloadWidget;
use Crumbls\Layup\View\FlipCardWidget;
use Crumbls\Layup\View\GalleryWidget;
use Crumbls\Layup\View\GradientTextWidget;
use Crumbls\Layup\View\HeadingWidget;
use Crumbls\Layup\View\HeroWidget;
use Crumbls\Layup\View\HighlightBoxWidget;
use Crumbls\Layup\View\HotspotWidget;
use Crumbls\Layup\View\HtmlWidget;
use Crumbls\Layup\View\IconBoxWidget;
use Crumbls\Layup\View\IconWidget;
use Crumbls\Layup\View\ImageHotspotWidget;
use Crumbls\Layup\View\ImageWidget;
use Crumbls\Layup\View\ListWidget;
use Crumbls\Layup\View\LoginWidget;
use Crumbls\Layup\View\LogoGridWidget;
use Crumbls\Layup\View\LogoSliderWidget;
use Crumbls\Layup\View\LottieWidget;
use Crumbls\Layup\View\MapWidget;
use Crumbls\Layup\View\MarqueeWidget;
use Crumbls\Layup\View\MasonryWidget;
use Crumbls\Layup\View\MenuWidget;
use Crumbls\Layup\View\MetricWidget;
use Crumbls\Layup\View\ModalWidget;
use Crumbls\Layup\View\NewsletterWidget;
use Crumbls\Layup\View\NotificationBarWidget;
use Crumbls\Layup\View\NumberCounterWidget;
use Crumbls\Layup\View\PersonWidget;
use Crumbls\Layup\View\PostListWidget;
use Crumbls\Layup\View\PriceWidget;
use Crumbls\Layup\View\PricingTableWidget;
use Crumbls\Layup\View\PricingToggleWidget;
use Crumbls\Layup\View\ProgressCircleWidget;
use Crumbls\Layup\View\RichTextWidget;
use Crumbls\Layup\View\SearchWidget;
use Crumbls\Layup\View\SeparatorWidget;
use Crumbls\Layup\View\ShareButtonsWidget;
use Crumbls\Layup\View\SkillBarWidget;
use Crumbls\Layup\View\SliderWidget;
use Crumbls\Layup\View\SocialFollowWidget;
use Crumbls\Layup\View\SocialProofWidget;
use Crumbls\Layup\View\SpacerWidget;
use Crumbls\Layup\View\StarRatingWidget;
use Crumbls\Layup\View\StatCardWidget;
use Crumbls\Layup\View\StepProcessWidget;
use Crumbls\Layup\View\TableOfContentsWidget;
use Crumbls\Layup\View\TableWidget;
use Crumbls\Layup\View\TabsWidget;
use Crumbls\Layup\View\TeamGridWidget;
use Crumbls\Layup\View\TestimonialCarouselWidget;
use Crumbls\Layup\View\TestimonialGridWidget;
use Crumbls\Layup\View\TestimonialSliderWidget;
use Crumbls\Layup\View\TestimonialWidget;
use Crumbls\Layup\View\TextWidget;
use Crumbls\Layup\View\TimelineWidget;
use Crumbls\Layup\View\ToggleWidget;
use Crumbls\Layup\View\TypewriterWidget;
use Crumbls\Layup\View\VideoPlaylistWidget;
use Crumbls\Layup\View\VideoWidget;

$widgets = [
    TextWidget::class,
    HeadingWidget::class,
    ImageWidget::class,
    ButtonWidget::class,
    VideoWidget::class,
    SpacerWidget::class,
    DividerWidget::class,
    HtmlWidget::class,
    BlurbWidget::class,
    IconWidget::class,
    AccordionWidget::class,
    ToggleWidget::class,
    TabsWidget::class,
    PersonWidget::class,
    TestimonialWidget::class,
    NumberCounterWidget::class,
    BarCounterWidget::class,
    GalleryWidget::class,
    AudioWidget::class,
    SliderWidget::class,
    MapWidget::class,
    CallToActionWidget::class,
    CountdownWidget::class,
    PricingTableWidget::class,
    SocialFollowWidget::class,
    CodeWidget::class,
    AlertWidget::class,
    TableWidget::class,
    EmbedWidget::class,
    ProgressCircleWidget::class,
    MenuWidget::class,
    SearchWidget::class,
    ContactFormWidget::class,
    StarRatingWidget::class,
    LogoGridWidget::class,
    BlockquoteWidget::class,
    FeatureListWidget::class,
    TimelineWidget::class,
    StatCardWidget::class,
    MarqueeWidget::class,
    BeforeAfterWidget::class,
    TeamGridWidget::class,
    NotificationBarWidget::class,
    TestimonialCarouselWidget::class,
    ComparisonTableWidget::class,
    HeroWidget::class,
    BreadcrumbsWidget::class,
    FaqWidget::class,
    LoginWidget::class,
    NewsletterWidget::class,
    PostListWidget::class,
    SeparatorWidget::class,
    BackToTopWidget::class,
    CookieConsentWidget::class,
    ShareButtonsWidget::class,
    ModalWidget::class,
    TypewriterWidget::class,
    CardWidget::class,
    TableOfContentsWidget::class,
    StepProcessWidget::class,
    GradientTextWidget::class,
    FlipCardWidget::class,
    PricingToggleWidget::class,
    ImageHotspotWidget::class,
    LottieWidget::class,
    MasonryWidget::class,
    RichTextWidget::class,
    ListWidget::class,
    AnchorWidget::class,
    BannerWidget::class,
    ContentToggleWidget::class,
    LogoSliderWidget::class,
    TestimonialSliderWidget::class,
    IconBoxWidget::class,
    AnimatedHeadingWidget::class,
    VideoPlaylistWidget::class,
    BadgeWidget::class,
    AvatarGroupWidget::class,
    TestimonialGridWidget::class,
    FileDownloadWidget::class,
    ChangelogWidget::class,
    SkillBarWidget::class,
    PriceWidget::class,
    HotspotWidget::class,
    MetricWidget::class,
    FeatureGridWidget::class,
    HighlightBoxWidget::class,
    SocialProofWidget::class,
];

foreach ($widgets as $widgetClass) {
    $shortName = class_basename($widgetClass);

    it("{$shortName}::getType() returns non-empty string", function () use ($widgetClass): void {
        expect($widgetClass::getType())->toBeString()->not->toBeEmpty();
    });

    it("{$shortName}::getLabel() returns non-empty string", function () use ($widgetClass): void {
        expect($widgetClass::getLabel())->toBeString()->not->toBeEmpty();
    });

    it("{$shortName}::getIcon() returns heroicon string", function () use ($widgetClass): void {
        expect($widgetClass::getIcon())->toBeString()->toStartWith('heroicon-');
    });

    it("{$shortName}::getCategory() is one of the allowed categories", function () use ($widgetClass): void {
        expect($widgetClass::getCategory())->toBeIn(['content', 'media', 'layout', 'interactive', 'advanced']);
    });

    it("{$shortName}::getFormSchema() returns array with Tabs", function () use ($widgetClass): void {
        $schema = $widgetClass::getFormSchema();
        expect($schema)->toBeArray()->not->toBeEmpty();
        expect($schema[0])->toBeInstanceOf(\Filament\Schemas\Components\Tabs::class);
    });

    it("{$shortName}::getContentFormSchema() returns array", function () use ($widgetClass): void {
        expect($widgetClass::getContentFormSchema())->toBeArray();
    });

    it("{$shortName}::getDefaultData() returns array", function () use ($widgetClass): void {
        expect($widgetClass::getDefaultData())->toBeArray();
    });

    it("{$shortName}::getPreview() with default data returns string", function () use ($widgetClass): void {
        $data = $widgetClass::getDefaultData();
        expect($widgetClass::getPreview($data))->toBeString();
    });

    it("{$shortName}::toArray() has required keys", function () use ($widgetClass): void {
        $arr = $widgetClass::toArray();
        expect($arr)->toHaveKeys(['type', 'label', 'icon', 'category', 'defaults']);
    });
}
