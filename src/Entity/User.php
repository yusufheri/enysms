<?php

namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *  fields={"email"},
 *  message="Un autre utilisateur possède déjà cette adresse mail, prière de trouver une autre"
 * )
 */
class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Email(message="Cette adresse {{value}} n'est pas une adresse mail valide")
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="Ce champ ne peut pas être vide")
     * @Assert\Length(min=6, max=255, minMessage="Le mot de passe doit contenir au moins 6 caractères",
     *  maxMessage="Le mot de passe doit contenir au maximum 255 caractères")
     */
    private $hash;

    
    /**
     * @Assert\EqualTo(propertyPath="hash", message="Vous n'avez pas correctement confirmé votre mot de passe !")
     * 
     *  @var String
     */
    private $confirmPassword;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="Ce champ ne peut pas être vide")
     * @Assert\Length(min=3, max=255, minMessage="Le mot de passe doit contenir au moins 3 caractères",
     *  maxMessage="Le mot de passe doit contenir au maximum 255 caractères")
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="Ce champ ne peut pas être vide")
     * @Assert\Length(min=3, max=255, minMessage="Le mot de passe doit contenir au moins 3 caractères",
     *  maxMessage="Le mot de passe doit contenir au maximum 255 caractères")
     */
    private $lastname;

    /**
     * @ORM\OneToMany(targetEntity=Balance::class, mappedBy="user")
     */
    private $balances;

    /**
     * @ORM\ManyToMany(targetEntity=Role::class, inversedBy="users")
     */
    private $roles;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Assert\Length(min=10, max=255, minMessage="Le mot de passe doit contenir au moins 10 caractères",
     *  maxMessage="Le mot de passe doit contenir au maximum 255 caractères")
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity=Favorite::class, mappedBy="user")
     */
    private $favorites;

    /**
     * @ORM\OneToMany(targetEntity=Group::class, mappedBy="user")
     */
    private $groups;

    /**
     * @ORM\OneToMany(targetEntity=Sender::class, mappedBy="user")
     */
    private $senders;

    /**
     * @ORM\OneToMany(targetEntity=Person::class, mappedBy="user")
     */
    private $people;

    /**
     * @ORM\OneToMany(targetEntity=Payment::class, mappedBy="customer")
     */
    private $payments;

    /**
     * @ORM\OneToMany(targetEntity=Payment::class, mappedBy="user")
     */
    private $userPayments;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $enabled;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="users")
     */
    private $owner;

    /**
     * @ORM\OneToMany(targetEntity=User::class, mappedBy="owner")
     */
    private $users;

    public function __construct()
    {
        $this->balances = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->roles = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->senders = new ArrayCollection();
        $this->people = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->userPayments = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getFullName(){
        return $this->firstname.' '.$this->lastname;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getConfirmPassword(): ?string
    {
        return $this->confirmPassword;
    }

    public function setConfirmPassWord(string $confirmPassword): self
    {
        $this->confirmPassword = $confirmPassword;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getTotalBalance()
    {
        $balances = $this->balances;
       if ($balances->count() > 0) {
           return $balances->last()->getCumul();
       } 
       return 0;
        
    }

    public function getSentSMS(){
       
        $messages= $this->favorites->map(function($favorite){
            return count($favorite->getMessages());
        })->toArray();

        return array_reduce($messages, function($v1, $v2){
            return $v1 + $v2;
        }, 0);
    }

    public function getBalance()
    {
        return ($this->getTotalBalance() - $this->getSentSMS());
    }

    

    /**
     * @return Collection|Balance[]
     */
    public function getBalances(): Collection
    {
        return $this->balances;
    }

    public function addBalance(Balance $balance): self
    {
        if (!$this->balances->contains($balance)) {
            $this->balances[] = $balance;
            $balance->setUser($this);
        }

        return $this;
    }

    public function removeBalance(Balance $balance): self
    {
        if ($this->balances->contains($balance)) {
            $this->balances->removeElement($balance);
            // set the owning side to null (unless already changed)
            if ($balance->getUser() === $this) {
                $balance->setUser(null);
            }
        }

        return $this;
    }

    public function getRoles(){
        $roles = $this->roles->map(function($role){
            return $role->getLibelle();
        })->toArray();

        $roles [] = "ROLE_USER";

        return $roles;
    }

    public function getSalt(){}

    public function getPassword(){
        return $this->hash;
    }

    public function getUsername(){
        return $this->email;
    }

    public function eraseCredentials(){
        
    }

    /**
     * @return Collection|Role[]
     */
    public function getRolesUsers(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function removeRole(Role $role): self
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection|Favorite[]
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorite $favorite): self
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites[] = $favorite;
            $favorite->setUser($this);
        }

        return $this;
    }

    public function removeFavorite(Favorite $favorite): self
    {
        if ($this->favorites->contains($favorite)) {
            $this->favorites->removeElement($favorite);
            // set the owning side to null (unless already changed)
            if ($favorite->getUser() === $this) {
                $favorite->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Group[]
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Group $group): self
    {
        if (!$this->groups->contains($group)) {
            $this->groups[] = $group;
            $group->setUser($this);
        }

        return $this;
    }

    public function removeGroup(Group $group): self
    {
        if ($this->groups->contains($group)) {
            $this->groups->removeElement($group);
            // set the owning side to null (unless already changed)
            if ($group->getUser() === $this) {
                $group->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Sender[]
     */
    public function getSenders(): Collection
    {
        return $this->senders;
    }

    public function addSender(Sender $sender): self
    {
        if (!$this->senders->contains($sender)) {
            $this->senders[] = $sender;
            $sender->setUser($this);
        }

        return $this;
    }

    public function removeSender(Sender $sender): self
    {
        if ($this->senders->contains($sender)) {
            $this->senders->removeElement($sender);
            // set the owning side to null (unless already changed)
            if ($sender->getUser() === $this) {
                $sender->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Person[]
     */
    public function getPeople(): Collection
    {
        return $this->people;
    }

    public function addPerson(Person $person): self
    {
        if (!$this->people->contains($person)) {
            $this->people[] = $person;
            $person->setUser($this);
        }

        return $this;
    }

    public function removePerson(Person $person): self
    {
        if ($this->people->contains($person)) {
            $this->people->removeElement($person);
            // set the owning side to null (unless already changed)
            if ($person->getUser() === $this) {
                $person->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Payment[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments[] = $payment;
            $payment->setCustomer($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payments->contains($payment)) {
            $this->payments->removeElement($payment);
            // set the owning side to null (unless already changed)
            if ($payment->getCustomer() === $this) {
                $payment->setCustomer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Payment[]
     */
    public function getUserPayments(): Collection
    {
        return $this->userPayments;
    }

    public function addUserPayment(Payment $userPayment): self
    {
        if (!$this->userPayments->contains($userPayment)) {
            $this->userPayments[] = $userPayment;
            $userPayment->setUser($this);
        }

        return $this;
    }

    public function removeUserPayment(Payment $userPayment): self
    {
        if ($this->userPayments->contains($userPayment)) {
            $this->userPayments->removeElement($userPayment);
            // set the owning side to null (unless already changed)
            if ($userPayment->getUser() === $this) {
                $userPayment->setUser(null);
            }
        }

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getOwner(): ?self
    {
        return $this->owner;
    }

    public function setOwner(?self $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(self $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setOwner($this);
        }

        return $this;
    }

    public function removeUser(self $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            // set the owning side to null (unless already changed)
            if ($user->getOwner() === $this) {
                $user->setOwner(null);
            }
        }

        return $this;
    }
}
