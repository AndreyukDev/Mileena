<?php

declare(strict_types=1);

namespace Mileena\DBMQ;

/**
 * Defines the contract for a Data Transfer Object (DTO).
 *
 * A DTO is a simple object used to transfer data between different layers of an application,
 * such as from the database layer to the business logic or view layer.
 *
 * Implementing this interface ensures that a class can be both constructed from an array
 * and serialized back into an array, providing a consistent data handling mechanism.
 */
interface DTO
{
    /**
     * Creates a new instance of the DTO from an associative array.
     *
     * This factory method is responsible for mapping the array keys to the object's properties.
     * It should handle cases where the input array is null or incomplete.
     *
     * @param array<string, mixed>|null $data The associative array containing the DTO's data.
     * @return static|null A new instance of the class, or null if the data is insufficient to create an object.
     */
    public static function fromArray(?array $data): ?self;

    /**
     * Converts the DTO instance into an associative array.
     *
     * This method is responsible for serializing the object's properties into a key-value array,
     * making it suitable for JSON encoding or for being saved to a database.
     *
     * @return array<string, mixed> An associative array representing the object's state.
     */
    public function toArray(): array;
}
