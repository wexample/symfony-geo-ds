<?php

namespace Wexample\SymfonyGeoDs\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Type\SelectInputType;
use Wexample\SymfonyForms\Form\Type\TextareaInputType;
use Wexample\SymfonyForms\Form\Type\TextInputType;
use Wexample\SymfonyGeo\Entity\Country;
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
    private const string LABELS = 'WexampleSymfonyGeoDsBundle.common.address::';

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
                ->add('firstName', TextInputType::class, ['label' => self::LABELS.'first_name', 'required' => $options['required']])
                ->add('lastName', TextInputType::class, ['label' => self::LABELS.'last_name', 'required' => $options['required']]);
        }

        $builder
            ->add('postalAddress', TextareaInputType::class, [
                'label' => self::LABELS.'postal_address',
                'required' => $options['required'],
                'rows' => 2,
            ])
            ->add('postCode', TextInputType::class, ['label' => self::LABELS.'post_code', 'required' => $options['required']])
            ->add('city', TextInputType::class, ['label' => self::LABELS.'city', 'required' => $options['required']])
            ->add('country', SelectInputType::class, [
                'label' => self::LABELS.'country',
                'required' => $options['required'],
                'choices' => $this->countries->findBy([], ['name' => 'ASC']),
                'choice_value' => static fn (?Country $country) => $country?->getIsoAlpha2Code(),
                'choice_label' => static fn (Country $country) => $country->getName(),
                'auto_translate_choices' => false,
                'placeholder' => self::LABELS.'country_placeholder',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'with_name' => false,
            'label' => false,
        ]);

        $resolver->setAllowedTypes('with_name', 'bool');
    }

    public function getBlockPrefix(): string
    {
        return 'postal_address';
    }
}
