# CS306-car-rental-database
=======
# Araç Kiralama Sistemi

Bu proje, bir araç kiralama sisteminin veritabanı işlemlerini gerçekleştiren ve web arayüzü sunan bir Flask uygulamasıdır. Sistem, müşteri rezervasyonlarını yönetme, araç takibi, çalışan performans raporları ve araç hasar/bakım takibini gerçekleştirir.

## Özellikler

- **Rezervasyon Yönetimi:** Müşteriler için manuel ID ile yeni rezervasyon oluşturma.
- **Araç Takip:** Şubelerdeki araçları görüntüleme ve listeleme.
- **Çalışan Yönetimi:** Çalışan performans raporlarını sorgulama.
- **Hasar/Bakım Takip:** Araçların hasar geçmişini görüntüleme.

## Trigger ve Otomatik İşlemler

Sistem aşağıdaki trigger'lara sahiptir:

1. **check_rental_period:** Kiralama bitiş tarihinin başlangıç tarihinden sonra olduğunu kontrol eder.
2. **damage_record_notification:** Bir araca hasar kaydı eklendiğinde otomatik bildirim oluşturur.
3. **log_salary_changes:** Çalışan maaşında değişiklik olduğunda log tutar.
4. **check_insurance_validity:** Sigorta bitiş tarihinin başlangıç tarihinden sonra olduğunu kontrol eder.

## Kurulum

### Gereksinimler

- Python 3.6 veya üzeri
- MySQL veritabanı

### Adımlar

1. Bu repoyu klonlayın:
```
git clone https://github.com/username/RENTACAR.git
cd RENTACAR
```

2. Sanal ortam oluşturun ve etkinleştirin (opsiyonel):
```
python -m venv .venv
# Windows
.venv\Scripts\activate
# macOS/Linux
source .venv/bin/activate
```

3. Gerekli paketleri yükleyin:
```
pip install -r requirements.txt
```

4. Veritabanı oluşturma:

   a. MySQL'de RENTACAR adında bir veritabanı oluşturun
   
   ```sql
   CREATE DATABASE RENTACAR;
   ```
   
   b. Veritabanı tablolarını oluşturun:
   ```
   python create_tables_in_db.py
   ```
   
   c. Örnek verileri yükleyin:
   ```
   python insert_data.py
   ```
   
   d. Stored procedure'ları oluşturun:
   ```
   python setup_procedures.py
   ```

5. `app.py` dosyasındaki veritabanı bağlantı bilgilerini kendi bilgilerinizle güncelleyin:
```python
def get_db_connection():
    connection = pymysql.connect(host='localhost',
                                user='your_username',
                                password='your_password',
                                database='RENTACAR',
                                cursorclass=DictCursor)
    return connection
```

## Çalıştırma

Uygulamayı başlatmak için:
```
python app.py
```

Web tarayıcınızda `http://localhost:5001` adresine giderek uygulamayı kullanabilirsiniz.

## Kullanım

### 1. Rezervasyon Oluşturma
- Ana sayfadan "Rezervasyon Yönetimi" seçeneğine tıklayın
- Rezervasyon ID, Müşteri ID, Araç ID ve tarih bilgilerini girin
- "Rezervasyon Oluştur" butonuna tıklayın

### 2. Şube Araçlarını Görüntüleme
- Ana sayfadan "Araç Takip" seçeneğine tıklayın
- Şube ID'sini girin (örn. 200, 201)
- "Araçları Göster" butonuna tıklayın

### 3. Çalışan Performansını Görüntüleme
- Ana sayfadan "Çalışan Yönetimi" seçeneğine tıklayın
- Çalışan ID'sini girin
- "Performans Göster" butonuna tıklayın

### 4. Araç Hasar Geçmişini Görüntüleme
- Ana sayfadan "Hasar/Bakım Takip" seçeneğine tıklayın
- Araç ID'sini girin
- "Hasar Geçmişi Göster" butonuna tıklayın

### 5. Trigger'ları Test Etme
Triggerların çalışmasını test etmek için aşağıdaki SQL sorgularını MySQL Workbench'te kullanabilirsiniz:

#### Hasar Bildirimi (damage_record_notification)
```sql
-- Yeni bir hasar kaydı ekle (car_id'yi gerçek bir değerle değiştirin)
INSERT INTO Car_has_damage_record (damage_id, car_id, description, repair_cost)
VALUES (8000, 53, 'Ön tampon çarpma hasarı', 750.00);

-- Bildirimi kontrol et
SELECT * FROM Notifications ORDER BY notification_id DESC LIMIT 1;
```

#### Maaş Değişikliği Logu (log_salary_changes)
```sql
-- Bir çalışanın maaşını güncelle (emp_id'yi gerçek bir değerle değiştirin)
UPDATE Employee SET salary = salary + 1000 WHERE emp_id = 300;

-- Log kaydını kontrol et
SELECT * FROM SalaryChangeLog ORDER BY log_id DESC LIMIT 1;
```

## Lisans

Bu proje MIT lisansı altında lisanslanmıştır. 
>>>>>>> Stashed changes
