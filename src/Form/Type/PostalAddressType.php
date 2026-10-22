<?php

namespace Wexample\SymfonyGeoDs\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Type\SelectInputType;
use Wexample\SymfonyForms\Form\Type\TextareaInputType;
use Wexample\SymfonyForms\Form\Type\TextInputType;
use Wexample\SymfonyGeo\Entity\Country;
use Wexample\SymfonyGeo\Interface\PostalAddressInterface;
use Wexample\SymfonyGeo\Repository\CountryRepository;

/**
 * A postal address, as one field of any form: the street lines, the post
 * code and the city, the country picked in the countries' table — and, asked
 * for (`with_name`), the addressee. It fills whatever holds those properties:
 * an address of symfony-geo (AbstractAddress), or an object of the form's.
 *
 * Its words are its own (`common/address`), whichever form it stands in.
 */
class PostalAddressType extends AbstractType
{
    private const string DOMAIN = 'WexampleSymfonyGeoDsBundle.common.address';

    public function __construct(
        private readonly CountryRepository $countries,
    ) {
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        if ($options['with_name']) {
            $builder
                ->add('firstName', TextInputType::class, ['label' => 'first_name', 'translation_domain' => self::DOMAIN, 'required' => $options['required']])
                ->add('lastName', TextInputType::class, ['label' => 'last_name', 'translation_domain' => self::DOMAIN, 'required' => $options['required']]);
        }

        $builder
            ->add('postalAddress', TextareaInputType::class, [
                'label' => 'postal_address',
                'translation_domain' => self::DOMAIN,
                'required' => $options['required'],
                'rows' => 2,
            ])
            ->add('postCode', TextInputType::class, ['label' => 'post_code', 'translation_domain' => self::DOMAIN, 'required' => $options['required']])
            ->add('city', TextInputType::class, ['label' => 'city', 'translation_domain' => self::DOMAIN, 'required' => $options['required']])
            ->add('country', SelectInputType::class, [
                'label' => 'country',
                'translation_domain' => self::DOMAIN,
                'required' => $options['required'],
                'choices' => $this->sortedCountries(),
                'choice_value' => static fn (?Country $country) => $country?->getIsoAlpha2Code(),
                'choice_label' => static fn (Country $country) => self::countryName($country),
                'choice_translation_domain' => false,
                'auto_translate_choices' => false,
                'placeholder' => 'country_placeholder',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Whatever holds an address: its own fields are read and written on
        // it. A form without data to start from names a class to build.
        $resolver->setDefaults([
            'data_class' => PostalAddressInterface::class,
            // Its words, whichever form it stands in.
            'translation_domain' => self::DOMAIN,
            'with_name' => false,
            'label' => false,
        ]);

        $resolver->setAllowedTypes('with_name', 'bool');
    }

    public function getBlockPrefix(): string
    {
        return 'postal_address';
    }

    /**
     * A country as the page's language names it, when that language knows it.
     */
    private static function countryName(Country $country): string
    {
        return Countries::exists($country->getIsoAlpha2Code())
            ? Countries::getName($country->getIsoAlpha2Code())
            : $country->getName();
    }

    /**
     * @return list<Country> In the order of their names, as they are read.
     */
    private function sortedCountries(): array
    {
        $countries = $this->countries->findAll();
        usort($countries, static fn (Country $a, Country $b) => strcoll(self::countryName($a), self::countryName($b)));

        return $countries;
    }
}
