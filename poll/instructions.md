# Polling Application Specification

## Overview
A simple polling application using PHP 8.4 without any database.

## Configuration

### JSON Configuration File
The application uses a JSON configuration file to store:
- **General poll status**: Whether polls are active or inactive
- **List of questions**: Each question contains:
  - Title (displayed on the main page)
  - Question text
  - Unique ID for the program
- **Possible answers**: For each question, a list of available answers
  - **Answer IDs**: Manually set in the JSON file for each answer

**Configuration file location**: `config.json`

## User Management

### Cookie-based User Identification
- On page arrival, check if a cookie is set for the user
- Cookie contains a user GUID
- If no cookie exists, generate a new GUID and set the cookie
- **Cookie duration**: 30 days

## Data Storage

### Answer Storage Structure
Answers are stored in the filesystem to handle concurrent access:
- **Directory structure**: One folder per question in `poll/data/`
- **File naming**: `{QUESTION_ID}/{USER_GUID}_{ANSWER_ID}.txt`
- Each user can only answer once per question
- When a user changes their answer, the previous file is deleted
- **Concurrency handling**: Use PHP's `flock()` mechanism for file locking

## User Interface

### Main Page (Poll List)
- Display all available polls
- Show poll titles
- Provide buttons to navigate to each specific poll page

### Poll Specific Page

#### Before Voting
- Display the question
- Show possible answers as large buttons
- Use icons instead of text (except for question and answers)

#### After Voting
- Store the answer when clicked
- Stay on the same page
- Display current results for that question
- Show count for each answer
- Display results as column bars with counts using CSS and Bootstrap
- **Auto-refresh**: Results update automatically every 2 seconds using AJAX calls
- **Note**: Results are only visible to users who have voted

#### Changing Answer
- Provide a button to remove the previous answer
- Allow selection of a new answer
- Previous answer file is deleted when changed

#### Navigation
- Back button (icon) to return to the poll list

### Colors
- Use colors around: #634a99

## Access Control

### Inactive Polls
When the general poll status is set to "inactive":
- Users can still navigate to each question page
- Results remain visible for everyone
- No new votes can be submitted

### Error Handling
- If a user tries to access a poll that doesn't exist in the config, use simple PHP `header()` redirect to the home page

## Administration
- No admin interface provided
- All configuration managed through manual JSON file editing
- Prevent access to configuration and data files via web server settings

## Layout & Design

### Styling
- Clean and mobile-friendly design
- Use latest Bootstrap CSS from CDN
- Use Bootstrap Icons library

### UI Elements
- Large buttons for answer selection
- Clear display of results with column bars
- Minimal text on poll pages (only question and answers)
- Icons for all navigation and actions (back button, refresh indicator, etc.)

## Technical Requirements
- PHP 8.4
- No database
- Concurrent access handling for file operations using `flock()`
- Responsive design for mobile devices