<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'register.errors.email_exists')]
#[Vich\Uploadable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'register.errors.email_required')]
    #[Assert\Email(message: 'register.errors.email_invalid')]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'register.errors.first_name_required')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'register.errors.first_name_min', maxMessage: 'register.errors.first_name_max')]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'register.errors.last_name_required')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'register.errors.last_name_min', maxMessage: 'register.errors.last_name_max')]
    private ?string $lastName = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $activationToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $activationTokenExpiresAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $status = 'active';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $passwordChangedAt = null;

    #[ORM\Column]
    private bool $emailVerified = false;

    #[ORM\Column]
    private bool $twoFactorEnabled = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $language = 'fr';

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $currency = 'EUR';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $timezone = 'Europe/Paris';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[Vich\UploadableField(mapping: 'profile_pictures', fileNameProperty: 'profilePicture')]
    private ?File $profilePictureFile = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $pendingEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailVerificationToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $emailVerificationTokenExpiresAt = null;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Account::class, cascade: ['persist', 'remove'])]
    private Collection $accounts;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Document::class)]
    private Collection $documents;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: KycSubmission::class, cascade: ['persist', 'remove'])]
    private ?KycSubmission $kycSubmission = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->accounts = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getPasswordChangedAt(): ?\DateTimeImmutable
    {
        return $this->passwordChangedAt;
    }

    public function setPasswordChangedAt(?\DateTimeImmutable $passwordChangedAt): static
    {
        $this->passwordChangedAt = $passwordChangedAt;
        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function setEmailVerified(bool $emailVerified): static
    {
        $this->emailVerified = $emailVerified;
        return $this;
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    public function setTwoFactorEnabled(bool $twoFactorEnabled): static
    {
        $this->twoFactorEnabled = $twoFactorEnabled;
        return $this;
    }

    /**
     * @return Collection<int, Account>
     */
    public function getAccounts(): Collection
    {
        return $this->accounts;
    }

    public function addAccount(Account $account): static
    {
        if (!$this->accounts->contains($account)) {
            $this->accounts->add($account);
            $account->setOwner($this);
        }
        return $this;
    }

    public function removeAccount(Account $account): static
    {
        if ($this->accounts->removeElement($account)) {
            if ($account->getOwner() === $this) {
                $account->setOwner(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setOwner($this);
        }
        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getOwner() === $this) {
                $document->setOwner(null);
            }
        }
        return $this;
    }

    public function getPrimaryAccount(): ?Account
    {
        $primaryAccount = $this->accounts->filter(function (Account $account) {
            return $account->getType() === 'checking' && $account->getStatus() === 'active';
        })->first();

        return $primaryAccount ?: null;
    }

    public function hasActiveAccount(): bool
    {
        return $this->accounts->exists(function ($key, Account $account) {
            return $account->getStatus() === 'active';
        });
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles);
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): static
    {
        $this->language = $language;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): static
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function __toString(): string
    {
        return $this->getFullName() . ' (' . $this->email . ')';
    }

    public function getActivationToken(): ?string
    {
        return $this->activationToken;
    }

    public function setActivationToken(?string $activationToken): static
    {
        $this->activationToken = $activationToken;
        return $this;
    }

    public function getActivationTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->activationTokenExpiresAt;
    }

    public function setActivationTokenExpiresAt(?\DateTimeImmutable $activationTokenExpiresAt): static
    {
        $this->activationTokenExpiresAt = $activationTokenExpiresAt;
        return $this;
    }

    public function generateActivationToken(): static
    {
        $this->activationToken = bin2hex(random_bytes(32));
        $this->activationTokenExpiresAt = new \DateTimeImmutable('+24 hours');
        return $this;
    }

    public function isActivationTokenValid(): bool
    {
        if (!$this->activationToken || !$this->activationTokenExpiresAt) {
            return false;
        }
        
        return $this->activationTokenExpiresAt > new \DateTimeImmutable();
    }

    public function clearActivationToken(): static
    {
        $this->activationToken = null;
        $this->activationTokenExpiresAt = null;
        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;
        return $this;
    }

    public function getProfilePictureFile(): ?File
    {
        return $this->profilePictureFile;
    }

    public function setProfilePictureFile(?File $profilePictureFile = null): static
    {
        $this->profilePictureFile = $profilePictureFile;

        if (null !== $profilePictureFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getPendingEmail(): ?string
    {
        return $this->pendingEmail;
    }

    public function setPendingEmail(?string $pendingEmail): static
    {
        $this->pendingEmail = $pendingEmail;
        return $this;
    }

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function setEmailVerificationToken(?string $emailVerificationToken): static
    {
        $this->emailVerificationToken = $emailVerificationToken;
        return $this;
    }

    public function getEmailVerificationTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->emailVerificationTokenExpiresAt;
    }

    public function setEmailVerificationTokenExpiresAt(?\DateTimeImmutable $emailVerificationTokenExpiresAt): static
    {
        $this->emailVerificationTokenExpiresAt = $emailVerificationTokenExpiresAt;
        return $this;
    }

    public function isEmailVerificationTokenValid(): bool
    {
        return $this->emailVerificationToken !== null 
            && $this->emailVerificationTokenExpiresAt !== null
            && $this->emailVerificationTokenExpiresAt > new \DateTimeImmutable();
    }

    public function generateEmailVerificationToken(): string
    {
        $this->emailVerificationToken = bin2hex(random_bytes(32));
        $this->emailVerificationTokenExpiresAt = new \DateTimeImmutable('+24 hours');
        return $this->emailVerificationToken;
    }

    public function clearEmailVerificationToken(): static
    {
        $this->emailVerificationToken = null;
        $this->emailVerificationTokenExpiresAt = null;
        return $this;
    }

    public function getKycSubmission(): ?KycSubmission
    {
        return $this->kycSubmission;
    }

    public function setKycSubmission(?KycSubmission $kycSubmission): static
    {
        // Unset the owning side of the relation if necessary
        if ($kycSubmission === null && $this->kycSubmission !== null) {
            $this->kycSubmission->setUser(null);
        }

        // Set the owning side of the relation if necessary
        if ($kycSubmission !== null && $kycSubmission->getUser() !== $this) {
            $kycSubmission->setUser($this);
        }

        $this->kycSubmission = $kycSubmission;
        return $this;
    }

    public function hasKycSubmission(): bool
    {
        return $this->kycSubmission !== null;
    }

    public function isKycApproved(): bool
    {
        return $this->hasKycSubmission() && $this->kycSubmission->isApproved();
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function isClient(): bool
    {
        return $this->hasRole('ROLE_CLIENT') || $this->hasRole('ROLE_ADMIN') || $this->hasRole('ROLE_SUPER_ADMIN');
    }
}
