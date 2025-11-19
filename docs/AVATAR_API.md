# Avatar Customization API Documentation

## Overview

The Avatar Customization System allows users to create personalized 2D avatars with customizable colors and unlockable items (hats, accessories, backgrounds, etc.). This document provides complete API documentation for implementing the avatar system in mobile apps (iOS Swift and Android Flutter).

## Base URL

```
https://your-domain.com/wp-json/gread/v1/
```

## Authentication

Most endpoints require authentication. Include the authentication token in the request headers:

```
Authorization: Bearer YOUR_JWT_TOKEN
```

Or use WordPress nonce:

```
X-WP-Nonce: YOUR_NONCE
```

---

## Endpoints

### 1. Get User Avatar (SVG)

**GET** `/avatar/{user_id}`

Returns the user's avatar as SVG markup.

#### Parameters

- `user_id` (required): The user ID
- `size` (optional): Avatar size in pixels (default: 200, max: 1000)

#### Example Request

```bash
GET /gread/v1/avatar/123?size=300
```

#### Example Response

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="300" height="300">
    <!-- SVG content -->
</svg>
```

#### Implementation Notes

- **iOS (Swift)**: Use `WKWebView` or SVG parsing library to display
- **Flutter**: Use `flutter_svg` package to render SVG strings

---

### 2. Get Current User's Avatar Customization

**GET** `/avatar/customization`

Returns the current user's avatar customization settings.

#### Example Request

```bash
GET /gread/v1/avatar/customization
```

#### Example Response

```json
{
  "body_color": "#FFFFFF",
  "gender": "male",
  "shirt_color": "#4A90E2",
  "pants_color": "#2C3E50",
  "equipped_items": [3, 7, 12]
}
```

---

### 3. Update Avatar Customization

**POST** `/avatar/customization`

Updates the current user's avatar customization.

#### Request Body

```json
{
  "body_color": "#FFFFFF",
  "gender": "female",
  "shirt_color": "#FF5733",
  "pants_color": "#2C3E50",
  "equipped_items": [3, 7]
}
```

#### Parameters

- `body_color` (optional): Hex color for body (e.g., "#FFFFFF")
- `gender` (optional): "male" or "female"
- `shirt_color` (optional): Hex color for shirt
- `pants_color` (optional): Hex color for pants
- `equipped_items` (optional): Array of item IDs to equip

#### Example Response

```json
{
  "body_color": "#FFFFFF",
  "gender": "female",
  "shirt_color": "#FF5733",
  "pants_color": "#2C3E50",
  "equipped_items": [3, 7]
}
```

#### Error Responses

**403 Forbidden** - Item is locked
```json
{
  "code": "item_locked",
  "message": "One or more items are not unlocked",
  "data": {
    "status": 403
  }
}
```

---

### 4. Get Available Avatar Items

**GET** `/avatar/items`

Returns all available avatar items with lock/unlock status.

#### Example Response

```json
{
  "items": [
    {
      "id": 1,
      "slug": "hat-baseball",
      "name": "Baseball Cap",
      "category": "hat",
      "is_unlocked": true,
      "unlock_metric": "books_read",
      "unlock_value": 5,
      "unlock_message": "Read 5 books to unlock!",
      "svg_preview": "<svg>...</svg>"
    }
  ],
  "grouped": {
    "hat": [...],
    "accessory": [...],
    "background": [...]
  }
}
```

---

### 5. Get Full Customization Data (Mobile-Optimized)

**GET** `/avatar/customization/full`

⭐ **Best endpoint for mobile apps** - Returns everything needed to build the customization UI in a single call.

#### Example Request

```bash
GET /gread/v1/avatar/customization/full
```

#### Example Response

```json
{
  "current_customization": {
    "body_color": "#FFFFFF",
    "gender": "male",
    "shirt_color": "#4A90E2",
    "pants_color": "#2C3E50",
    "equipped_items": [3, 7]
  },
  "user_stats": {
    "points": 1250,
    "books_read": 42,
    "pages_read": 15000,
    "books_added": 5,
    "approved_reports": 3
  },
  "categories": [
    {
      "category_name": "Hat",
      "items": [
        {
          "id": 0,
          "slug": "none",
          "name": "None",
          "category": "hat",
          "svg_data": "",
          "is_unlocked": true,
          "unlock_metric": null,
          "unlock_value": 0,
          "unlock_message": "",
          "unlock_progress": 100,
          "current_value": 0
        },
        {
          "id": 2,
          "slug": "hat-baseball",
          "name": "Baseball Cap",
          "category": "hat",
          "svg_data": "<path d=\"M 30 25...\" fill=\"#FF5733\"/>",
          "is_unlocked": true,
          "unlock_metric": "books_read",
          "unlock_value": 5,
          "unlock_message": "Read 5 books to unlock!",
          "unlock_progress": 100,
          "current_value": 42
        },
        {
          "id": 5,
          "slug": "hat-crown",
          "name": "Crown",
          "category": "hat",
          "svg_data": "<path d=\"M 35 20...\" fill=\"#FFD700\"/>",
          "is_unlocked": false,
          "unlock_metric": "books_read",
          "unlock_value": 100,
          "unlock_message": "Read 100 books to unlock!",
          "unlock_progress": 42.0,
          "current_value": 42
        }
      ]
    },
    {
      "category_name": "Accessory",
      "items": [...]
    }
  ],
  "avatar_url": "https://your-domain.com/wp-json/gread/v1/avatar/123",
  "customization_endpoint": "https://your-domain.com/wp-json/gread/v1/avatar/customization",
  "gender_options": [
    { "value": "male", "label": "Male" },
    { "value": "female", "label": "Female" }
  ]
}
```

---

### 6. Get User Avatar Data

**GET** `/avatar/{user_id}/data`

Get avatar customization data for a specific user (for displaying on profiles).

#### Example Request

```bash
GET /gread/v1/avatar/123/data
```

#### Example Response

```json
{
  "user_id": 123,
  "username": "John Doe",
  "body_color": "#FFFFFF",
  "gender": "male",
  "shirt_color": "#4A90E2",
  "pants_color": "#2C3E50",
  "equipped_items": [3, 7],
  "avatar_url": "https://your-domain.com/wp-json/gread/v1/avatar/123"
}
```

---

## Mobile Implementation Guide

### iOS (Swift) Example

```swift
import UIKit

class AvatarCustomizationViewController: UIViewController {

    func loadCustomizationData() {
        let url = URL(string: "https://your-domain.com/wp-json/gread/v1/avatar/customization/full")!
        var request = URLRequest(url: url)
        request.setValue("Bearer \(authToken)", forHTTPHeaderField: "Authorization")

        URLSession.shared.dataTask(with: request) { data, response, error in
            guard let data = data else { return }

            do {
                let result = try JSONDecoder().decode(AvatarCustomizationResponse.self, from: data)
                DispatchQueue.main.async {
                    self.displayCustomizationUI(with: result)
                }
            } catch {
                print("Error decoding: \(error)")
            }
        }.resume()
    }

    func updateAvatar(customization: AvatarCustomization) {
        let url = URL(string: "https://your-domain.com/wp-json/gread/v1/avatar/customization")!
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("Bearer \(authToken)", forHTTPHeaderField: "Authorization")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")

        let encoder = JSONEncoder()
        request.httpBody = try? encoder.encode(customization)

        URLSession.shared.dataTask(with: request) { data, response, error in
            // Handle response
        }.resume()
    }
}

struct AvatarCustomization: Codable {
    let bodyColor: String
    let gender: String
    let shirtColor: String
    let pantsColor: String
    let equippedItems: [Int]

    enum CodingKeys: String, CodingKey {
        case bodyColor = "body_color"
        case gender
        case shirtColor = "shirt_color"
        case pantsColor = "pants_color"
        case equippedItems = "equipped_items"
    }
}
```

### Flutter (Dart) Example

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:flutter_svg/flutter_svg.dart';

class AvatarService {
  final String baseUrl = 'https://your-domain.com/wp-json/gread/v1';
  final String authToken;

  AvatarService(this.authToken);

  Future<Map<String, dynamic>> getFullCustomizationData() async {
    final response = await http.get(
      Uri.parse('$baseUrl/avatar/customization/full'),
      headers: {
        'Authorization': 'Bearer $authToken',
      },
    );

    if (response.statusCode == 200) {
      return json.decode(response.body);
    } else {
      throw Exception('Failed to load customization data');
    }
  }

  Future<void> updateCustomization(AvatarCustomization customization) async {
    final response = await http.post(
      Uri.parse('$baseUrl/avatar/customization'),
      headers: {
        'Authorization': 'Bearer $authToken',
        'Content-Type': 'application/json',
      },
      body: json.encode(customization.toJson()),
    );

    if (response.statusCode != 200) {
      throw Exception('Failed to update customization');
    }
  }

  Future<String> getUserAvatarSvg(int userId, {int size = 200}) async {
    final response = await http.get(
      Uri.parse('$baseUrl/avatar/$userId?size=$size'),
    );

    if (response.statusCode == 200) {
      return response.body;
    } else {
      throw Exception('Failed to load avatar');
    }
  }
}

class AvatarCustomization {
  final String bodyColor;
  final String gender;
  final String shirtColor;
  final String pantsColor;
  final List<int> equippedItems;

  AvatarCustomization({
    required this.bodyColor,
    required this.gender,
    required this.shirtColor,
    required this.pantsColor,
    required this.equippedItems,
  });

  Map<String, dynamic> toJson() => {
    'body_color': bodyColor,
    'gender': gender,
    'shirt_color': shirtColor,
    'pants_color': pantsColor,
    'equipped_items': equippedItems,
  };
}

// Widget to display avatar
class AvatarWidget extends StatelessWidget {
  final int userId;
  final int size;

  const AvatarWidget({
    required this.userId,
    this.size = 200,
  });

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<String>(
      future: AvatarService(authToken).getUserAvatarSvg(userId, size: size),
      builder: (context, snapshot) {
        if (snapshot.hasData) {
          return SvgPicture.string(
            snapshot.data!,
            width: size.toDouble(),
            height: size.toDouble(),
          );
        } else if (snapshot.hasError) {
          return Icon(Icons.error);
        }
        return CircularProgressIndicator();
      },
    );
  }
}
```

---

## Color Picker UI

### Recommended Color Pickers

**iOS:**
- Use `UIColorPickerViewController` (iOS 14+)
- Or custom RGB sliders

**Flutter:**
- `flutter_colorpicker` package
- `flex_color_picker` package

### Color Format

All colors must be in hex format: `#RRGGBB` (e.g., `#FF5733`)

---

## Item Categories

- `hat` - Hats and headwear
- `accessory` - Glasses, masks, etc.
- `background` - Background patterns/colors
- `shirt_pattern` - Patterns for shirts
- `pants_pattern` - Patterns for pants

---

## Unlock Metrics

Items can be unlocked based on:

- `points` - User points
- `books_read` - Number of books completed
- `pages_read` - Total pages read
- `books_added` - Books added to database
- `approved_reports` - Approved inaccuracy reports

---

## Best Practices

1. **Use `/avatar/customization/full` for initial load** - Get everything in one call
2. **Cache avatar SVGs** - Reduce API calls by caching avatar SVG data
3. **Show unlock progress** - Use `unlock_progress` and `current_value` to show users how close they are to unlocking items
4. **Validate colors** - Ensure colors are in hex format before sending
5. **Handle locked items** - Disable UI for locked items and show unlock requirements
6. **Real-time preview** - Generate SVG locally for preview before saving

---

## Error Codes

- `400` - Bad request (invalid parameters)
- `403` - Forbidden (item locked or permission denied)
- `404` - User not found
- `500` - Server error

---

## Support

For questions or issues, please contact the development team or file an issue on GitHub.
